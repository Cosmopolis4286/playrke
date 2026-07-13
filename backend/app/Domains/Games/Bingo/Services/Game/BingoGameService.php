<?php

namespace App\Domains\Games\Bingo\Services\Game;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Draw\BingoDrawCode;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\DTOs\Game\PlayerCardPricingDTO;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use App\Exceptions\Domain\ExchangeRate\ExchangeRateException;
use App\Models\Currency;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\Wallet;
use App\Domains\Games\Bingo\Services\Room\Utils\CheckExpiredBingoRoomsService;
use App\Services\ExchangeRate\CurrencyExchangeRateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class BingoGameService
{
    public function __construct(
        private readonly CheckExpiredBingoRoomsService $checkExpired,
        private readonly CurrencyExchangeRateService $currencyExchangeRate,
        private readonly BingoDrawService $drawService,
        private readonly BingoPatternPositionResolver $patternResolver,
    ) {}

    /**
     * Obtener datos para la vista del juego (game).
     *
     * @param BingoRoom $room
     * @param int|null $userId
     * @return array
     */
    public function getInGameData(BingoRoom $room, ?int $userId = null): array
    {
        $this->checkExpired->handle();

        // La sala ya viene del route binding, solo necesitamos cargar las relaciones
        $bingoRoom = $room->load(['config', 'creator', 'cardPrices', 'rounds', 'status']);

        if ($bingoRoom->trashed()) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('BingoRoom has been deleted.');
        }

        $roomStatusCode = $bingoRoom->getRelationValue('status')?->code
            ?? $bingoRoom->status?->code
            ?? '';
        $roomIsFinished = strtoupper($roomStatusCode) === 'FINISHED';

        // Obtener cartones del jugador (incluye ganadores si la sala ya terminó)
        $cartons = $this->getUserCartons((int) $bingoRoom->id, $userId, $roomIsFinished);

        // Resolver moneda, tasa de cambio y precio del cartón según la wallet del usuario
        $pricing = $this->resolvePricingForUser($bingoRoom, $userId);

        // Preparar datos del juego
        $config = $bingoRoom->config;
        $rounds = $bingoRoom->rounds;

        // Cargar números sorteados desde la base de datos
        $drawnNumbers = BingoDraw::where('room_id', $bingoRoom->id)
            ->orderBy('time')
            ->orderBy('created_at')
            ->pluck('chip')
            ->map(fn($chip) => (int) $chip)
            ->toArray();

        // Para salas finalizadas, cargar los ganadores históricos desde BingoCardPrize
        $initialWinners = $roomIsFinished ? $this->getHistoricalWinners($bingoRoom) : [];

        return [
            'room'         => $bingoRoom,
            'userId'       => $userId !== null ? (string) $userId : null,
            'roomId'       => (string) $bingoRoom->id,
            'roomRouteKey' => $bingoRoom->getRouteKey(),
            'gameVariant'  => $config?->game_variant ?? \App\Domains\Games\Bingo\ValueObjects\BingoVariant::BINGO_75,
            'cartons'      => $cartons,
            'customerLogo' => null,
            'currencySym'  => $pricing->currencySymbol,
            'prizePool'    => (float) $bingoRoom->prize_pool,
            'exchangeRate' => $pricing->exchangeRate,
            'totalPlayers' => (int) $bingoRoom->total_players,
            'prizePoolType'=> 'Premio',
            'round'        => 1,
            'rounds'       => (int) $rounds->count(),
            'bingoPattern' => '',
            'draw'         => $drawnNumbers,
            'startTime'    => (int) ($config?->unix_date ?? $bingoRoom->published_at?->timestamp ?? now()->timestamp),
            'initialWinners' => $initialWinners,
            'roomFinished' => $roomIsFinished,
        ];
    }

    /**
     * Estado actual del sorteo — usado por el cliente para resincronizar
     * tras una reconexión de WebSocket (eventos perdidos mientras el socket
     * estuvo caído, ya que Reverb/Pusher no reenvía el historial).
     *
     * @param BingoRoom $room
     * @return array
     */
    public function getDrawState(BingoRoom $room): array
    {
        // Cacheado 3s — bajo tormenta de reconexiones (muchos clientes simultáneos)
        // todas las consultas de historial de fichas golpearían la misma tabla a la vez.
        $drawnNumbers = Cache::remember(
            "bingo:draw-state:draw:{$room->id}",
            3,
            fn () => BingoDraw::where('room_id', $room->id)
                ->orderBy('time')
                ->orderBy('created_at')
                ->pluck('chip')
                ->map(fn($chip) => (int) $chip)
                ->toArray()
        );

        if (!$room->relationLoaded('status')) $room->load('status');
        $roomStatusCode = $room->status?->code ?? '';
        $roomIsFinished = strtoupper($roomStatusCode) === 'FINISHED';

        // players_progress: solo cuando quedan ≤10 fichas y el sorteo sigue activo.
        // Cacheado 3s por sala+cantidad de fichas — bajo una desconexión masiva de
        // WS, muchos clientes reconectan casi a la vez y llaman a este endpoint en
        // paralelo; sin cache, cada uno recalcula ronda+patrón+progreso de todos
        // los cartones de la sala, multiplicando carga de DB en el peor momento.
        $playersProgress = [];
        if (!$room->relationLoaded('config')) $room->load('config');
        $variant    = $room->config?->game_variant ?? BingoVariant::BINGO_75;
        $totalChips = BingoVariant::totalChips($variant);
        if (!$roomIsFinished && count($drawnNumbers) > 0 && ($totalChips - count($drawnNumbers)) <= 10) {
            $cacheKey = "bingo:draw-state:players-progress:{$room->id}:" . count($drawnNumbers);
            $playersProgress = Cache::remember($cacheKey, 3, function () use ($room, $variant, $drawnNumbers) {
                // Obtener el patrón desde la ronda activa de la sala
                $round   = $room->rounds()->latest('round')->first();
                $pattern = $round?->bingo_pattern_id
                    ? BingoPattern::find($round->bingo_pattern_id)
                    : BingoPattern::where('default_pattern', 'Y')->first();

                if (!$pattern) {
                    return [];
                }

                $patternPositions = $this->patternResolver->resolve($pattern, $variant);
                return $this->drawService->computePlayersProgress($room, $patternPositions, $drawnNumbers);
            });
        }

        return [
            'draw'            => $drawnNumbers,
            'totalPlayers'    => (int) $room->total_players,
            'prizePool'       => (float) $room->prize_pool,
            'roomFinished'    => $roomIsFinished,
            'initialWinners'  => $roomIsFinished ? $this->getHistoricalWinners($room) : [],
            'playersProgress' => $playersProgress,
        ];
    }

    /**
     * Obtener cartones del usuario en una sala.
     *
     * @param int $roomId
     * @param int|null $userId
     * @param bool $includeWinners  true para salas finalizadas — incluye STATUS_WINNER además de STATUS_ACTIVE
     * @return array
     */
    public function getUserCartons(int $roomId, ?int $userId, bool $includeWinners = false): array
    {
        if (!$userId) {
            return [];
        }

        $roomUser = \App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser::where('room_id', (string) $roomId)
            ->where('user_id', (string) $userId)
            ->first();

        $validStatuses = $includeWinners
            ? [\App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_ACTIVE, \App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_WINNER]
            : [\App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_ACTIVE];

        $query = \App\Domains\Games\Bingo\Models\Card\BingoCard::query()
            ->whereIn('status', $validStatuses)
            ->with(['currency', 'cardLot', 'roomUser'])
            ->orderBy('serial_number');

        if ($roomUser) {
            $query->where('room_user_id', (string) $roomUser->id);
        } else {
            // Fallback: creador directo sin room_user (ej. sala propia en dev)
            $query->where('room_id', (string) $roomId)
                  ->where('creator_user_id', (string) $userId);
        }

        return $query->get()
            ->map(fn ($card) => $card->toFrontendFormat())
            ->all();
    }

    /**
     * Resuelve moneda, tasa de cambio y precio del cartón para el jugador,
     * según la wallet activa que tenga configurada (si tiene alguna).
     */
    private function resolvePricingForUser(BingoRoom $room, ?int $userId): PlayerCardPricingDTO
    {
        $wallet           = $this->resolveUserWallet($userId);
        $walletCurrencyId = $wallet?->currency_id ? (int) $wallet->currency_id : null;

        return new PlayerCardPricingDTO(
            currencySymbol: $wallet?->currency?->symbol ?? '$',
            currencyId:     $walletCurrencyId,
            exchangeRate:   $this->getWalletExchangeRate($walletCurrencyId),
            cardPrice:      $this->getCardPrice($room, $walletCurrencyId),
        );
    }

    /**
     * Ganadores históricos de una sala ya finalizada, para reconstruir el
     * estado de "sorteo terminado" al recargar la página del juego.
     *
     * @return array<int, array{user_id: string, user_name: string, card_id: string, card_serial: string, prize: float, pattern: string, round: int}>
     */
    private function getHistoricalWinners(BingoRoom $room): array
    {
        return BingoCardPrize::where('room_id', $room->id)
            ->with(['round:id,round', 'winningCard:id,serial_number', 'roomUser.user:id,username,first_name,last_name'])
            ->get()
            ->map(fn (BingoCardPrize $cp) => [
                'user_id'     => (string) ($cp->roomUser?->user_id ?? ''),
                'user_name'   => $cp->roomUser?->user?->full_name ?? $cp->roomUser?->user?->username ?? '—',
                'card_id'     => (string) ($cp->winning_card_id ?? ''),
                'card_serial' => $cp->winningCard?->serial_number ?? '',
                'prize'       => (float) ($cp->usd_amount ?? 0),
                'pattern'     => $cp->winning_pattern ?? '',
                'round'       => $cp->round?->round ?? 1,
            ])
            ->values()
            ->all();
    }

    /**
     * [DEV] Actualizar unix_date y time de la sala a now+2 minutos.
     * Si existe un sorteo previo, lo regenera.
     * Solo debe llamarse en entorno local.
     *
     * @param BingoRoom $room
     * @return array  Nuevo startTime, time legible y estado de regeneración
     */
    public function devResetStartTime(BingoRoom $room): array
    {
        $newTime   = now()->addMinutes(2);
        $unixDate  = $newTime->timestamp;

        $hasExistingDraw = BingoDraw::where('room_id', $room->id)->exists();
        $hasExistingCode = BingoDrawCode::where('room_id', $room->id)->exists();
        $hadData = $hasExistingDraw || $hasExistingCode;

        // Full DEV reset: clear all draw data, prizes, rounds, cards and room status
        DB::transaction(function () use ($room) {
            BingoDraw::where('room_id', $room->id)->delete();
            BingoDrawCode::where('room_id', $room->id)->delete();
            BingoCardPrize::where('room_id', $room->id)->delete();

            // Restaurar cartones ganadores a ACTIVE para que el siguiente sorteo los detecte
            \App\Domains\Games\Bingo\Models\Card\BingoCard::where('room_id', (string) $room->id)
                ->where('status', \App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_WINNER)
                ->update(['status' => \App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_ACTIVE]);

            $room->rounds()->update(['status' => 'ACTIVE']);

            // Restore room to ACTIVE so the game can be re-drawn
            $statusMap    = GameStatus::codesMap(['ACTIVE', 'IN_PROGRESS']);
            $restoreId    = $statusMap['ACTIVE'] ?? $statusMap['IN_PROGRESS'] ?? null;
            if ($restoreId) {
                $room->update(['status_id' => $restoreId, 'finished_at' => null]);
            }
        });

        $room->config()->update([
            'unix_date' => $unixDate,
            'date'      => $newTime->toDateString(),
            'time'      => $newTime->format('H:i:s'),
        ]);

        Log::info('[DEV] Sala reseteada para nuevo sorteo', [
            'room_id'  => $room->id,
            'had_data' => $hadData,
        ]);

        // Emitir evento para sincronizar el tiempo con todos los clientes conectados
        broadcast(new \App\Domains\Games\Bingo\Events\RoomTimeSync($room->id, $unixDate))->toOthers();

        return [
            'startTime'       => $unixDate,
            'humanTime'       => $newTime->format('H:i:s'),
            'hasExistingDraw' => $hadData,
            'message'         => $hadData ? 'Sorteo regenerado exitosamente' : 'Nuevo sorteo iniciado',
        ];
    }

    /**
     * Obtener el precio del cartón para una sala
     *
     * @param BingoRoom $room
     * @return float
     */
    private function getCardPrice(BingoRoom $room, ?int $walletCurrencyId = null): float
    {
        if ($walletCurrencyId) {
            $localPrice = $room->cardPrices->firstWhere('currency_id', $walletCurrencyId);

            if ($localPrice) {
                return (float) $localPrice->card_price;
            }
        }

        // Buscar precio en USD (moneda por defecto)
        $usdId = Currency::usdId();
        $cardPrice = $usdId
            ? $room->cardPrices->firstWhere('currency_id', $usdId)
            : null;

        // Si no hay precio en USD, tomar el primero disponible
        if (!$cardPrice && $room->cardPrices->isNotEmpty()) {
            $cardPrice = $room->cardPrices->first();
        }

        $price = (float) ($cardPrice?->card_price ?? 0);
        $isWalletUsd = $walletCurrencyId && (string) $walletCurrencyId === (string) $usdId;

        return $walletCurrencyId && !$isWalletUsd
            ? $this->convertToWalletCurrency($price, $walletCurrencyId)
            : $price;
    }

    private function resolveUserWallet(?int $userId): ?Wallet
    {
        if (!$userId) {
            return null;
        }

        return Wallet::with('currency')
            ->where('user_id', $userId)
            ->active()
            ->first();
    }

    /**
     * Tasa de cambio de la billetera del jugador — delega a la fuente única
     * de verdad (CurrencyExchangeRateService → exchange_rates). Vista de solo
     * lectura (precio mostrado antes de comprar): si no hay tasa activa no se
     * bloquea la carga de la página, pero queda registrado en logs para que
     * se corrija antes de que el jugador intente comprar (ahí sí se bloquea,
     * ver BingoCardGenerateService::getPlayerCardPrice()).
     */
    private function getWalletExchangeRate(?int $currencyId): float
    {
        if (!$currencyId) {
            return 1.0;
        }

        try {
            return $this->currencyExchangeRate->getActiveRate($currencyId);
        } catch (ExchangeRateException $e) {
            Log::warning('[BingoGameService] Sin tasa activa al resolver precio para el jugador', [
                'currency_id' => $currencyId,
                'error'       => $e->getMessage(),
            ]);
            return 1.0;
        }
    }

    private function convertToWalletCurrency(float $amount, ?int $currencyId): float
    {
        if (!$currencyId) {
            return round($amount, 2);
        }

        return round($amount * $this->getWalletExchangeRate($currencyId), 2);
    }
}

<?php

namespace App\Services\Games\Bingo\Game;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Draw\BingoDrawCode;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\DTOs\Game\PlayerCardPricingDTO;
use App\Models\ExchangeRate;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\Wallet;
use App\Services\Games\Bingo\Room\Utils\CheckExpiredBingoRoomsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class BingoGameService
{
    public function __construct(
        private readonly CheckExpiredBingoRoomsService $checkExpired,
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

        // Obtener cartones del jugador
        $cartons = $this->getUserCartons((int) $bingoRoom->id, $userId);

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

        $roomStatusCode = $bingoRoom->getRelationValue('status')?->code
            ?? $bingoRoom->status?->code
            ?? '';
        $roomIsFinished = strtoupper($roomStatusCode) === 'FINISHED';

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
     * Obtener cartones del usuario en una sala.
     *
     * @param int $roomId
     * @param int|null $userId
     * @return array
     */
    public function getUserCartons(int $roomId, ?int $userId): array
    {
        if (!$userId) {
            return [];
        }

        $roomUser = \App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser::where('room_id', (string) $roomId)
            ->where('user_id', (string) $userId)
            ->first();

        $query = \App\Domains\Games\Bingo\Models\Card\BingoCard::query()
            ->where('status', \App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_ACTIVE)
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

        // Full DEV reset: clear all draw data, prizes, rounds and room status
        DB::transaction(function () use ($room) {
            BingoDraw::where('room_id', $room->id)->delete();
            BingoDrawCode::where('room_id', $room->id)->delete();
            BingoCardPrize::where('room_id', $room->id)->delete();

            $room->rounds()->update(['status' => 'ACTIVE']);

            // Restore room to ACTIVE so the game can be re-drawn
            $activeStatus = GameStatus::where('code', 'ACTIVE')->first()
                ?? GameStatus::where('code', 'IN_PROGRESS')->first();
            if ($activeStatus) {
                $room->update(['status_id' => $activeStatus->id, 'finished_at' => null]);
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
        $cardPrice = $room->cardPrices
            ->where('currency_id', 1) // Asumiendo que 1 es USD
            ->first();

        // Si no hay precio en USD, tomar el primero disponible
        if (!$cardPrice && $room->cardPrices->isNotEmpty()) {
            $cardPrice = $room->cardPrices->first();
        }

        $price = (float) ($cardPrice?->card_price ?? 0);

        return $walletCurrencyId && $walletCurrencyId !== 1
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

    private function getWalletExchangeRate(?int $currencyId): float
    {
        if (!$currencyId || $currencyId === 1) {
            return 1.0;
        }

        $exchangeRate = ExchangeRate::where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('created_at')
            ->first();

        return $exchangeRate ? (float) $exchangeRate->amount : 1.0;
    }

    private function convertToWalletCurrency(float $amount, ?int $currencyId): float
    {
        if (!$currencyId || $currencyId === 1) {
            return round($amount, 2);
        }

        return round($amount * $this->getWalletExchangeRate($currencyId), 2);
    }
}

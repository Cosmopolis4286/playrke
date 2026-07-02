<?php

namespace App\Services\Games\Bingo\Game;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Draw\BingoDrawCode;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\ExchangeRate;
use App\Models\Games\Bingo\ExternalPlayers;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Games\Bingo\Room\Utils\CheckExpiredBingoRoomsService;
use Illuminate\Support\Facades\DB;

final class BingoGameService
{
    public function __construct(
        private readonly \App\Services\Security\UserContextService $userContext,
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

        // Verificar si la sala está eliminada (soft delete)
        if ($bingoRoom->trashed()) {
            \Log::warning('BingoRoom found but is soft deleted', [
                'room_id' => $bingoRoom->id,
                'uuid_code' => $bingoRoom->uuid_code,
                'deleted_at' => $bingoRoom->deleted_at
            ]);
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('BingoRoom has been deleted.');
        }

        // Obtener cartones del jugador
        $cartons = $this->getUserCartons((int) $bingoRoom->id, $userId);

        // Obtener la wallet del usuario y la moneda asociada (si existe)
        $wallet = $this->resolveUserWallet($userId);
        $walletCurrencyId = $wallet?->currency_id ? (int) $wallet->currency_id : null;
        $currencySym = $wallet?->currency?->symbol ?? '$';
        $exchangeRate = $this->getWalletExchangeRate($walletCurrencyId);

        // Obtener precio del cartón adaptado a la moneda de la wallet del usuario
        $cardPrice = $this->getCardPrice($bingoRoom, $walletCurrencyId);

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
        $initialWinners = [];
        $roomStatusCode = $bingoRoom->getRelationValue('status')?->code
            ?? $bingoRoom->status?->code
            ?? '';
        if (strtoupper($roomStatusCode) === 'FINISHED') {
            $initialWinners = BingoCardPrize::where('room_id', $bingoRoom->id)
                ->with(['round:id,round', 'winningCard:id,serial_number', 'roomUser.user:id,username,first_name,last_name'])
                ->get()
                ->map(function (BingoCardPrize $cp) use ($bingoRoom) {
                    return [
                        'user_id'     => (string) ($cp->roomUser?->user_id ?? ''),
                        'user_name'   => $cp->roomUser?->user?->full_name ?? $cp->roomUser?->user?->username ?? '—',
                        'card_id'     => (string) ($cp->winning_card_id ?? ''),
                        'card_serial' => $cp->winningCard?->serial_number ?? '',
                        'prize'       => (float) ($cp->usd_amount ?? 0),
                        'pattern'     => $cp->winning_pattern ?? '',
                        'round'       => $cp->round?->round ?? 1,
                    ];
                })
                ->values()
                ->all();
        }

        $gameData = [
            'title' => 'Juego de Bingo',
            'room' => $bingoRoom,
            'userId'       => $userId !== null ? (string) $userId : null,
            'roomId'       => (string) $bingoRoom->id,
            'roomRouteKey' => $bingoRoom->getRouteKey(),
            'gameVariant'  => $config?->game_variant ?? \App\Domains\Games\Bingo\ValueObjects\BingoVariant::BINGO_75,
            'cartons' => $cartons,
            'customerLogo' => null, // Se puede configurar según necesidad
            'currencySym' => $currencySym,
            'prizePool' => (float) $bingoRoom->prize_pool, // Enviar en USD
            'exchangeRate' => $exchangeRate, // Enviar la tasa de cambio
            'totalPlayers' => (int) $bingoRoom->total_players,
            'prizePoolType' => 'Premio',
            'round' => 1, // Obtener de la configuración del juego
            'rounds' => $rounds->count(), // Número de rondas desde la relación
            'bingoPattern' => '', // Obtener del patrón activo
            'vanDraw' => [], // Obtener números sorteados
            'player' => ['ctns' => count($cartons)],
            'encrypt' => false,
            'chipsRest' => [],
            'draw' => $drawnNumbers,
            'timeServ' => now()->timestamp,
            'allChips' => [],
            'infoGame' => null,
            'creatorType' => 'admin', // Obtener del creador de la sala
            'creatorId' => $bingoRoom->creator_user_id,
            'currencyId' => (string) ($walletCurrencyId ?? 1),
            'cardPrice' => (float) $cardPrice,
            'startTime' => (int) ($config?->unix_date ?? $bingoRoom->published_at?->timestamp ?? now()->timestamp),
            'closingRoute' => route('bingo-rooms.index'),
            'initialWinners' => $initialWinners,
        ];

        // Forzar la serialización correcta para Inertia
        $gameData['rounds'] = (int) $gameData['rounds'];

        return $gameData;
    }

    /**
     * Obtener datos legacy para la vista del juego (compatibilidad).
     *
     * @param string $player
     * @param string $room
     * @return array
     */
    public function getLegacyInGameData(string $player, string $room = ''): array
    {
        // Vista para el juego de la venta conjunta de todos los comercios
        $playerModel = ExternalPlayers::getByLink($player, ['room', 'creator_user_id', 'link', 'currency']);

        if (!$playerModel) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Player not found');
        }

        $creatorUser = $playerModel->creator_user;
        $userCreator = User::getCreatorUser($creatorUser, false, true);

        if (!$userCreator) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Creator user not found');
        }

        $creator = [
            'type' => $userCreator['type'],
            'id' => $userCreator['id'],
            'userId' => $creatorUser
        ];

        return [
            'title' => 'Juego de Bingo',
            'playerLink' => $playerModel->link,
            'room' => $playerModel->room,
            'currency' => $playerModel->currency,
            'creator' => $creator,
            'customerLogo' => null,
            'currencySym' => '$',
            'prizePool' => $playerModel->room->prizePool ?? 0,
            'prizePoolType' => 'Premio',
            'round' => 1,
            'bingoPattern' => '',
            'vanDraw' => [],
            'player' => ['ctns' => 0],
            'encrypt' => false,
            'chipsRest' => [],
            'draw' => [],
            'timeServ' => now()->timestamp,
            'allChips' => [],
            'infoGame' => null,
            'creatorType' => $creator['type'],
            'creatorId' => $creator['id'],
            'currencyId' => 1,
            'cardPrice' => $playerModel->room->cardPrice ?? 0,
            'startTime' => $playerModel->room->publicationDate->timestamp ?? now()->timestamp,
            'closingRoute' => route('bingo-rooms.index'),
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

        try {
            // Obtener el room_user_id para este usuario en esta sala
            // Castear a string porque room_id y user_id son Snowflake (bigint guardado como string)
            $roomUser = \App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser::where('room_id', (string) $roomId)
                ->where('user_id', (string) $userId)
                ->first();

            if (!$roomUser) {
                // Si no hay room_user, intentar obtener cartones directamente por creator_user_id
                $cards = \App\Domains\Games\Bingo\Models\Card\BingoCard::where('room_id', (string)$roomId)
                    ->where('creator_user_id', (string)$userId)
                    ->where('status', \App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_ACTIVE)
                    ->with(['currency', 'cardLot', 'roomUser'])
                    ->orderBy('serial_number')
                    ->get();
            } else {
                // Obtener los cartones del usuario usando el room_user_id
                $cards = \App\Domains\Games\Bingo\Models\Card\BingoCard::where('room_user_id', (string)$roomUser->id)
                    ->where('status', \App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_ACTIVE)
                    ->with(['currency', 'cardLot', 'roomUser'])
                    ->orderBy('serial_number')
                    ->get();
            }

            // Transformar los cartones al formato esperado por el frontend
            return $cards->map(fn ($card) => $card->toFrontendFormat())->toArray();
        } catch (\Exception $e) {
            \Log::error('Error getting user cartons', [
                'room_id' => $roomId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
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

        \Log::info('[DEV] Sala reseteada para nuevo sorteo', [
            'room_id'  => $room->id,
            'had_data' => $hadData,
        ]);

        // Emitir evento para sincronizar el tiempo con todos los clientes conectados
        broadcast(new \App\Events\Games\Bingo\RoomTimeSync($room->id, $unixDate))->toOthers();

        return [
            'startTime'       => $unixDate,
            'humanTime'       => $newTime->format('H:i:s'),
            'hasExistingDraw' => $hadData,
            'message'         => $hadData ? 'Sorteo regenerado exitosamente' : 'Nuevo sorteo iniciado',
        ];
    }

    /**
     * Validar formato del identificador de sala
     *
     * @param string $identifier
     * @return bool
     */
    private function isValidRoomIdentifier(string $identifier): bool
    {
        // Verificar si es un UUID válido (formato: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $identifier)) {
            return true;
        }

        // Verificar si es un ID Snowflake válido (número grande, generalmente 15+ dígitos)
        if (preg_match('/^\d{15,}$/', $identifier)) {
            return true;
        }

        // Verificar si es un UUID sin guiones (formato alternativo)
        if (preg_match('/^[0-9a-f]{32}$/i', $identifier)) {
            return true;
        }

        // Verificar si es un ID corto alfanumérico (como 4xZOjvaxq3G2d)
        if (preg_match('/^[a-zA-Z0-9]{8,}$/', $identifier)) {
            return true;
        }

        return false;
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

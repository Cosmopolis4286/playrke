<?php

namespace App\Services\Games\Bingo\Game;

use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\ExchangeRate;
use App\Models\Games\Bingo\ExternalPlayers;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Games\Bingo\Room\Utils\CheckExpiredBingoRoomsService;

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
        $bingoRoom = $room->load(['config', 'creator', 'cardPrices', 'rounds']);

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

        // Debug logging para verificar los cartones obtenidos
        \Log::info('User cartons loaded', [
            'room_id' => $bingoRoom->id,
            'user_id' => $userId,
            'cartons_count' => count($cartons),
            'cartons_data' => $cartons
        ]);

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

        // Debug logging para verificar la configuración y rondas
        \Log::info('BingoRoom data', [
            'room_id' => $bingoRoom->id,
            'config_exists' => !is_null($config),
            'rounds_count' => $rounds->count(),
            'rounds_data' => $rounds->toArray(),
            'config_rounds' => $config?->rounds ?? 'NOT_FOUND'
        ]);

        // Cargar números sorteados desde la base de datos
        $drawnNumbers = BingoDraw::where('room_id', $bingoRoom->id)
            ->orderBy('time')
            ->orderBy('created_at')
            ->pluck('chip')
            ->map(fn($chip) => (int) $chip)
            ->toArray();

        \Log::info('Drawn numbers loaded', [
            'room_id' => $bingoRoom->id,
            'drawn_count' => count($drawnNumbers),
            'drawn_numbers' => $drawnNumbers
        ]);

        $gameData = [
            'title' => 'Juego de Bingo',
            'room' => $bingoRoom,
            'roomId'       => (string) $bingoRoom->id,
            'roomRouteKey' => $bingoRoom->getRouteKey(),
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
        ];

        // Forzar la serialización correcta para Inertia
        $gameData['rounds'] = (int) $gameData['rounds'];

        // Debug final para verificar los datos que se envían
        \Log::info('Final game data for Inertia', [
            'data_keys' => array_keys($gameData),
            'rounds_value' => $gameData['rounds'],
            'rounds_type' => gettype($gameData['rounds']),
            'rounds_forced' => (int) $gameData['rounds']
        ]);

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

            \Log::info('Room user lookup', [
                'room_id' => $roomId,
                'user_id' => $userId,
                'room_user_found' => $roomUser ? true : false,
                'room_user_id' => $roomUser?->id
            ]);

            if (!$roomUser) {
                // Si no hay room_user, intentar obtener cartones directamente por creator_user_id
                \Log::info('Trying alternative method to get cartons');
                $cards = \App\Domains\Games\Bingo\Models\Card\BingoCard::where('room_id', (string)$roomId)
                    ->where('creator_user_id', (string)$userId)
                    ->where('status', \App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_ACTIVE)
                    ->with(['currency', 'cardLot', 'roomUser'])
                    ->orderBy('serial_number')
                    ->get();

                \Log::info('Alternative carton search result', [
                    'cards_count' => $cards->count(),
                    'cards_data' => $cards->toArray()
                ]);
            } else {
                // Obtener los cartones del usuario usando el room_user_id
                $cards = \App\Domains\Games\Bingo\Models\Card\BingoCard::where('room_user_id', (string)$roomUser->id)
                    ->where('status', \App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_ACTIVE)
                    ->with(['currency', 'cardLot', 'roomUser'])
                    ->orderBy('serial_number')
                    ->get();

                \Log::info('Standard carton search result', [
                    'room_user_id' => $roomUser->id,
                    'cards_count' => $cards->count(),
                    'cards_data' => $cards->toArray()
                ]);
            }

            // Transformar los cartones al formato esperado por el frontend
            $formattedCards = $cards->map(function ($card) {
                return $card->toFrontendFormat();
            })->toArray();

            \Log::info('Final formatted cartons', [
                'formatted_count' => count($formattedCards),
                'formatted_data' => $formattedCards
            ]);

            return $formattedCards;
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

        // Verificar si existe un sorteo previo
        $hasExistingDraw = \App\Domains\Games\Bingo\Models\Draw\BingoDraw::where('room_id', $room->id)->exists();

        if ($hasExistingDraw) {
            // Resetear estados de las rondas
            $room->rounds()->update(['status' => 'ACTIVE']);

            // Limpiar sorteos anteriores
            \App\Domains\Games\Bingo\Models\Draw\BingoDraw::where('room_id', $room->id)->delete();

            \Log::info('[DEV] Sorteo previo regenerado', [
                'room_id' => $room->id,
                'room_uuid' => $room->uuid_code,
            ]);
        }

        $room->config()->update([
            'unix_date' => $unixDate,
            'date'      => $newTime->toDateString(),
            'time'      => $newTime->format('H:i:s'),
        ]);

        // Emitir evento para sincronizar el tiempo con todos los clientes conectados
        broadcast(new \App\Events\Games\Bingo\RoomTimeSync($room->id, $unixDate))->toOthers();

        return [
            'startTime'       => $unixDate,
            'humanTime'       => $newTime->format('H:i:s'),
            'hasExistingDraw' => $hasExistingDraw,
            'message'         => $hasExistingDraw ? 'Sorteo regenerado exitosamente' : 'Nuevo sorteo iniciado',
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

<?php

namespace App\Services\Games\Bingo\Game;

use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Games\Bingo\ExternalPlayers;
use App\Models\User;
use App\Services\Games\Bingo\Room\Utils\CheckExpiredBingoRoomsService;

final class BingoGameService
{
    public function __construct(
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly CheckExpiredBingoRoomsService $checkExpired,
    ) {}

    /**
     * Obtener datos para la vista del juego (in-game).
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
        $cartons = $this->getUserCartons($bingoRoom->id, $userId);

        // Obtener precio del cartón (USD por defecto)
        $cardPrice = $this->getCardPrice($bingoRoom);

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
        
        $gameData = [
            'title' => 'Juego de Bingo',
            'room' => $bingoRoom,
            'cartons' => $cartons,
            'customerLogo' => null, // Se puede configurar según necesidad
            'currencySym' => '$', // Configurable según moneda de la sala
            'prizePool' => (float) $bingoRoom->prize_pool,
            'prizePoolType' => 'Premio',
            'round' => 1, // Obtener de la configuración del juego
            'rounds' => $rounds->count(), // Número de rondas desde la relación
            'bingoPattern' => '', // Obtener del patrón activo
            'vanDraw' => [], // Obtener números sorteados
            'player' => ['ctns' => count($cartons)],
            'encrypt' => false,
            'chipsRest' => [],
            'draw' => [],
            'timeServ' => now()->timestamp,
            'allChips' => [],
            'infoGame' => null,
            'creatorType' => 'admin', // Obtener del creador de la sala
            'creatorId' => $bingoRoom->creator_user_id,
            'currencyId' => (string) 1, // Configurable
            'cardPrice' => (float) $cardPrice,
            'startTime' => $bingoRoom->published_at?->timestamp ?? now()->timestamp,
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
    private function getUserCartons(int $roomId, ?int $userId): array
    {
        if (!$userId) {
            return [];
        }

        // Aquí se debería obtener los cartones del usuario
        // Por ahora retornamos un array vacío como ejemplo
        // TODO: Implementar lógica real de obtención de cartones
        return [];
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
    private function getCardPrice(BingoRoom $room): float
    {
        // Buscar precio en USD (moneda por defecto)
        $cardPrice = $room->cardPrices
            ->where('currency_id', 1) // Asumiendo que 1 es USD
            ->first();

        // Si no hay precio en USD, tomar el primero disponible
        if (!$cardPrice && $room->cardPrices->isNotEmpty()) {
            $cardPrice = $room->cardPrices->first();
        }

        return $cardPrice?->card_price ?? 0;
    }
}

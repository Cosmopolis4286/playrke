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
     * @param string $room
     * @param int|null $userId
     * @return array
     */
    public function getInGameData(string $room, ?int $userId = null): array
    {
        $this->checkExpired->handle();
        
        // Buscar la sala por UUID o ID
        $bingoRoom = BingoRoom::where('uuid_code', $room)
            ->orWhere('id', $room)
            ->with(['Config', 'creator'])
            ->firstOrFail();

        // Obtener cartones del jugador
        $cartons = $this->getUserCartons($bingoRoom->id, $userId);

        // Preparar datos del juego
        return [
            'title' => 'Juego de Bingo',
            'room' => $bingoRoom,
            'cartons' => $cartons,
            'customerLogo' => null, // Se puede configurar según necesidad
            'currencySym' => '$', // Configurable según moneda de la sala
            'prizePool' => $bingoRoom->prizePool,
            'prizePoolType' => 'Premio',
            'round' => 1, // Obtener de la configuración del juego
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
            'creatorId' => $bingoRoom->creator_id,
            'currencyId' => 1, // Configurable
            'cardPrice' => $bingoRoom->cardPrice,
            'startTime' => $bingoRoom->publicationDate->timestamp,
            'closingRoute' => route('bingo-rooms.index'),
        ];
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
}

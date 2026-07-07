<?php

namespace App\Contracts\Games\Bingo\Room;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de salas de Bingo.
 *
 * Define métodos específicos de dominio para operaciones con BingoRoom
 * más allá del CRUD base proporcionado por BaseRepositoryInterface.
 */
interface BingoRoomRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Verificar si una sala existe por código UUID
     *
     * @param string $uuidCode Código UUID de la sala
     * @return bool
     */
    public function existsByUUIDCode(string $uuidCode): bool;

    /**
     * Actualizar pool de premios
     *
     * @param string $roomId ID de la sala
     * @param float $amount Monto a agregar/actualizar
     * @return bool
     */
    public function updatePrizePool(string $roomId, float $amount): bool;

    public function changeGameStatus(string $roomId,string $newStatusCode): bool;

    /**
     * Obtener salas activas de un usuario para la vista de juego (game-rooms) con filtrado por rol.
     * Si el usuario tiene rol 'player', solo devuelve salas PUBLIC.
     * Para roles commerce/customer, filtra por from_house = N.
     * Para otros roles, devuelve todas las salas activas.
     *
     * @param int    $userId ID del usuario creador
     * @param string $today  Fecha en formato YYYY-MM-DD
     * @param bool   $isPlayer Si el usuario tiene rol 'player'
     * @param bool   $isCommerceCustomer Si el usuario tiene rol 'commerce' o 'customer'
     * @return Collection<BingoRoom>
     */
    public function getActiveRoomsForViewWithRoleFilter(int $userId, string $today, bool $isPlayer, bool $isCommerceCustomer = false, bool $isAdmin = false): Collection;
}

<?php

namespace App\Domains\Games\Bingo\Contracts\Room;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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

    /**
     * Paginar el listado admin/commerce de salas (Room/Summary) con filtros
     * reales aplicados en SQL, en vez de traer una sola página fija y filtrar
     * en el cliente.
     *
     * @param array<string,mixed> $conditions Condiciones directas sobre bingo_rooms (campo => valor)
     * @param array{
     *     search?: string,
     *     status?: string,
     *     room_type_id?: string,
     *     game_mode_id?: string,
     *     prize_pool_type_id?: string,
     *     from_house?: string,
     *     currency_id?: string,
     * } $filters Filtros sobre relaciones (config.*, status.code) + búsqueda por uuid_code
     * @param array<int,string|\Closure> $relations
     * @param array<int,string> $fields
     */
    public function paginateSummaryGameRooms(
        int $perPage,
        int $page,
        array $conditions,
        array $filters,
        array $relations,
        array $fields
    ): LengthAwarePaginator;
}

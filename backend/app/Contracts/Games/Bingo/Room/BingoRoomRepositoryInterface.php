<?php

namespace App\Contracts\Games\Bingo\Room;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Games\Bingo\Room\BingoRoom;
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
     * Buscar sala por código encriptado
     *
     * @param string $uuidCode Código UUID de la sala
     * @param array $columns Columnas a seleccionar
     * @return BingoRoom|null
     */
    public function findByUUIDCode(string $uuidCode, array $columns = ['*']): ?BingoRoom;

    /**
     * Buscar salas por creador
     *
     * @param string $creatorUserId ID del usuario creador
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoRoom>
     */
    public function findByCreator(string $creatorUserId, array $columns = ['*']): Collection;

    /**
     * Buscar salas por estado
     *
     * @param string $statusId ID del estado
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoRoom>
     */
    public function findByStatus(string $statusId, array $columns = ['*']): Collection;

    /**
     * Obtener salas activas
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoRoom>
     */
    public function getActiveRooms(array $columns = ['*']): Collection;

    /**
     * Obtener salas con premios pendientes de pago
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoRoom>
     */
    public function getRoomsWithPendingPrizes(array $columns = ['*']): Collection;

    /**
     * Obtener estadísticas de ventas de una sala
     *
     * @param string $roomId ID de la sala
     * @return array
     */
    public function getRoomSalesStats(string $roomId): array;

    /**
     * Buscar salas por rango de fechas
     *
     * @param string $startDate Fecha inicial
     * @param string $endDate Fecha final
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoRoom>
     */
    public function findByDateRange(string $startDate, string $endDate, array $columns = ['*']): Collection;

    /**
     * Obtener total de comisiones por sala
     *
     * @param string $roomId ID de la sala
     * @return array
     */
    public function getRoomCommissions(string $roomId): array;

    /**
     * Verificar si una sala existe por código UUID
     *
     * @param string $uuidCode Código UUID de la sala
     * @return bool
     */
    public function existsByUUIDCode(string $uuidCode): bool;

    /**
     * Obtener valor específico de una sala por ID
     *
     * @param string $roomId ID de la sala
     * @param string $column Columna a obtener
     * @return mixed
     */
    public function getRoomValue(string $roomId, string $column): mixed;

    /**
     * Obtener valor específico de una sala por código UUID
     *
     * @param string $uuidCode Código UUID de la sala
     * @param string $column Columna a obtener
     * @return mixed
     */
    public function getRoomValueByUUID(string $uuidCode, string $column): mixed;

    /**
     * Cerrar una sala
     *
     * @param string $roomId ID de la sala
     * @return bool
     */
    public function closeRoom(string $roomId): bool;

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
     * Obtener salas activas de un usuario para la vista de juego (game-rooms).
     * Incluye config (unix_date, card_price, prize_pool_type) ordenadas por hora.
     *
     * @param int    $userId ID del usuario creador
     * @param string $today  Fecha en formato YYYY-MM-DD
     * @return Collection<BingoRoom>
     */
    public function getActiveRoomsForView(int $userId, string $today): Collection;
}

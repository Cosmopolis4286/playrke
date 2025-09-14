<?php

namespace App\Repositories\Contracts\Games\Bingo;

use App\Repositories\Contracts\BaseRepositoryContract;
use App\Models\Games\Bingo\Rooms\BingoRoom;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato específico para el repositorio de salas de Bingo
 * Define métodos específicos para operaciones con BingoRoom
 */
interface BingoRoomRepositoryContract extends BaseRepositoryContract
{
    /**
     * Buscar sala por código encriptado
     *
     * @param string $encryptedCode Código encriptado de la sala
     * @param array $columns Columnas a seleccionar
     * @return BingoRoom|null
     */
    public function findByEncryptedCode(string $encryptedCode, array $columns = ['*']): ?BingoRoom;

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
     * Buscar salas por tipo
     *
     * @param string $roomType Tipo de sala (PV, PB)
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoRoom>
     */
    public function findByType(string $roomType, array $columns = ['*']): Collection;

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
     * Verificar si una sala existe por código encriptado
     *
     * @param string $encryptedCode Código encriptado
     * @return bool
     */
    public function existsByEncryptedCode(string $encryptedCode): bool;

    /**
     * Obtener valor específico de una sala por ID
     *
     * @param string $roomId ID de la sala
     * @param string $column Columna a obtener
     * @return mixed
     */
    public function getRoomValue(string $roomId, string $column): mixed;

    /**
     * Obtener valor específico de una sala por código encriptado
     *
     * @param string $encryptedCode Código encriptado
     * @param string $column Columna a obtener
     * @return mixed
     */
    public function getRoomValueByEncrypted(string $encryptedCode, string $column): mixed;

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
}
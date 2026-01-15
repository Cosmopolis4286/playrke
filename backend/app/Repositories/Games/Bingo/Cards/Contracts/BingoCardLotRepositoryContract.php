<?php

namespace App\Repositories\Games\Bingo\Cards\Contracts;

use App\Models\Bingo\Cards\BingoCardLot;
use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de lotes de cartones de bingo
 *
 * Define los métodos disponibles para gestionar lotes de cartones de bingo,
 * incluyendo operaciones CRUD y consultas específicas del dominio.
 *
 * @package App\Repositories\Contracts\Games\Bingo\Cards
 * @author Sistema de Gestión de Bingo
 * @version 1.0
 */
interface BingoCardLotRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtiene todos los lotes de una sala específica
     *
     * @param string $roomId ID de la sala de bingo
     * @param array $filters Filtros adicionales ['status' => true, 'user_id' => '...']
     * @return Collection<BingoCardLot> Lotes de la sala
     *
     * @example
     * $lotes = $repository->getLotsByRoom('123', ['status' => true]);
     */
    public function getLotsByRoom(string $roomId, array $filters = []): Collection;

    /**
     * Obtiene todos los lotes de un usuario en una sala
     *
     * @param string $roomUserId ID del usuario en la sala
     * @param bool|null $isActive Estado del lote (true, false o null para todos)
     * @return Collection<BingoCardLot> Lotes del usuario
     *
     * @example
     * $lotes = $repository->getLotsByRoomUser('456', true);
     */
    public function getLotsByRoomUser(string $roomUserId, ?bool $isActive = null): Collection;

    /**
     * Obtiene lotes por vendedor
     *
     * @param string $sellerId ID del vendedor
     * @param array $filters Filtros adicionales ['room_id' => '...', 'status' => true]
     * @return Collection<BingoCardLot> Lotes del vendedor
     *
     * @example
     * $lotes = $repository->getLotsBySeller('321', ['status' => true]);
     */
    public function getLotsBySeller(string $sellerId, array $filters = []): Collection;

    /**
     * Obtiene lotes por moneda
     *
     * @param string $currencyId ID de la moneda
     * @param array $filters Filtros adicionales ['room_id' => '...']
     * @return Collection<BingoCardLot> Lotes con la moneda especificada
     *
     * @example
     * $lotes = $repository->getLotsByCurrency('789');
     */
    public function getLotsByCurrency(string $currencyId, array $filters = []): Collection;

    /**
     * Obtiene lotes creados en un rango de fechas
     *
     * @param string $dateFrom Fecha de inicio (Y-m-d H:i:s)
     * @param string $dateTo Fecha de fin (Y-m-d H:i:s)
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardLot> Lotes en el rango
     *
     * @example
     * $lotes = $repository->getLotsByDateRange('2024-01-01', '2024-01-31');
     */
    public function getLotsByDateRange(string $dateFrom, string $dateTo, array $filters = []): Collection;

    /**
     * Obtiene estadísticas de lotes por sala
     *
     * @param string $roomId ID de la sala
     * @return array Estadísticas agregadas
     *
     * @example
     * $stats = $repository->getLotStatsByRoom('123');
     * // ['total_lots' => 10, 'total_cards' => 150, 'total_amount' => 3000.00]
     */
    public function getLotStatsByRoom(string $roomId): array;

    /**
     * Obtiene estadísticas de lotes por usuario
     *
     * @param string $roomUserId ID del usuario en la sala
     * @return array Estadísticas del usuario
     *
     * @example
     * $stats = $repository->getLotStatsByUser('456');
     * // ['total_lots' => 5, 'total_cards' => 75, 'total_spent' => 1500.00]
     */
    public function getLotStatsByUser(string $roomUserId): array;

    /**
     * Cambia el estado de múltiples lotes
     *
     * @param array $lotIds IDs de los lotes
     * @param bool $isActive Nuevo estado
     * @return int Número de lotes actualizados
     *
     * @example
     * $updated = $repository->changeLotsStatus(['1', '2'], false);
     */
    public function changeLotsStatus(array $lotIds, bool $isActive): int;

    /**
     * Obtiene lotes activos de una sala
     *
     * @param string $roomId ID de la sala
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoCardLot> Lotes activos
     *
     * @example
     * $lotes = $repository->getActiveLotsInRoom('123');
     */
    public function getActiveLotsInRoom(string $roomId, array $columns = ['*']): Collection;

    /**
     * Obtiene lotes inactivos de una sala
     *
     * @param string $roomId ID de la sala
     * @param array $columns Columnas a seleccionar
     * @return Collection<BingoCardLot> Lotes inactivos
     *
     * @example
     * $lotes = $repository->getInactiveLotsInRoom('123');
     */
    public function getInactiveLotsInRoom(string $roomId, array $columns = ['*']): Collection;

    /**
     * Calcula el total de comisiones por vendedor en un período
     *
     * @param string $sellerId ID del vendedor
     * @param string $dateFrom Fecha de inicio
     * @param string $dateTo Fecha de fin
     * @return float Total de comisiones
     *
     * @example
     * $total = $repository->calculateSellerCommissions('321', '2024-01-01', '2024-01-31');
     */
    public function calculateSellerCommissions(string $sellerId, string $dateFrom, string $dateTo): float;

    /**
     * Obtiene el lote más reciente de un usuario en una sala
     *
     * @param string $roomUserId ID del usuario en la sala
     * @param bool|null $isActive Filtrar por estado activo
     * @return BingoCardLot|null Último lote o null
     *
     * @example
     * $lote = $repository->getLatestUserLot('456', true);
     */
    public function getLatestUserLot(string $roomUserId, ?bool $isActive = null): ?BingoCardLot;

    /**
     * Busca lotes por monto de venta
     *
     * @param float $minAmount Monto mínimo
     * @param float $maxAmount Monto máximo
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardLot> Lotes en el rango de monto
     *
     * @example
     * $lotes = $repository->getLotsBySaleAmount(100.00, 500.00);
     */
    public function getLotsBySaleAmount(float $minAmount, float $maxAmount, array $filters = []): Collection;

    /**
     * Cuenta los lotes por estado en una sala
     *
     * @param string $roomId ID de la sala
     * @return array Conteo por estado ['active' => 10, 'inactive' => 5]
     *
     * @example
     * $counts = $repository->countLotsByStatus('123');
     */
    public function countLotsByStatus(string $roomId): array;
}
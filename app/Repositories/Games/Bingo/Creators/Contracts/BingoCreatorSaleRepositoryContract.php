<?php

namespace App\Repositories\Games\Bingo\Creators\Contracts;

use App\Repositories\BaseRepositoryContract;
use App\Models\Games\Bingo\Creators\BingoCreatorSale;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de ventas de creadores de salas de bingo
 *
 * Define los métodos que debe implementar el repositorio para gestionar
 * las ventas y comisiones de los creadores de salas de bingo.
 *
 * @package App\Repositories\Games\Bingo\Creators\Contracts
 * @author Sistema PlayRKE
 * @version 1.0
 */
interface BingoCreatorSaleRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtener venta por sala, creador y moneda
     *
     * @param string $roomId
     * @param string $creatorUserId
     * @param string $currency
     * @return BingoCreatorSale|null
     */
    public function findByRoomCreatorAndCurrency(string $roomId, string $creatorUserId, string $currency): ?BingoCreatorSale;

    /**
     * Verificar si existe una venta para sala, creador y moneda específicos
     *
     * @param string $roomId
     * @param string $creatorUserId
     * @param string|null $currency
     * @return bool
     */
    public function existsByRoomCreatorAndCurrency(string $roomId, string $creatorUserId, ?string $currency = null): bool;

    /**
     * Obtener todas las ventas por sala
     *
     * @param string $roomId
     * @return Collection<int, BingoCreatorSale>
     */
    public function getByRoom(string $roomId): Collection;

    /**
     * Obtener todas las ventas por creador
     *
     * @param string $creatorUserId
     * @return Collection<int, BingoCreatorSale>
     */
    public function getByCreatorUser(string $creatorUserId): Collection;

    /**
     * Obtener todas las ventas por moneda
     *
     * @param string $currency
     * @return Collection<int, BingoCreatorSale>
     */
    public function getByCurrency(string $currency): Collection;

    /**
     * Obtener total del premio acumulado por sala y moneda
     *
     * @param string $roomId
     * @param string $currency
     * @return float
     */
    public function getPrizePoolAmountByRoomAndCurrency(string $roomId, string $currency): float;

    /**
     * Obtener total de ventas por sala
     *
     * @param string $roomId
     * @return float
     */
    public function getTotalSalesByRoom(string $roomId): float;

    /**
     * Obtener total de comisiones RKE por sala
     *
     * @param string $roomId
     * @return float
     */
    public function getTotalRkeCommissionByRoom(string $roomId): float;

    /**
     * Obtener total de comisiones de sala por sala
     *
     * @param string $roomId
     * @return float
     */
    public function getTotalRoomCommissionByRoom(string $roomId): float;

    /**
     * Obtener total de comisiones de vendedor por sala
     *
     * @param string $roomId
     * @return float
     */
    public function getTotalSellerCommissionByRoom(string $roomId): float;

    /**
     * Obtener total de ventas por creador
     *
     * @param string $creatorUserId
     * @return float
     */
    public function getTotalSalesByCreator(string $creatorUserId): float;

    /**
     * Obtener total de comisiones RKE por creador
     *
     * @param string $creatorUserId
     * @return float
     */
    public function getTotalRkeCommissionByCreator(string $creatorUserId): float;

    /**
     * Obtener total de comisiones de sala por creador
     *
     * @param string $creatorUserId
     * @return float
     */
    public function getTotalRoomCommissionByCreator(string $creatorUserId): float;

    /**
     * Obtener total de comisiones de vendedor por creador
     *
     * @param string $creatorUserId
     * @return float
     */
    public function getTotalSellerCommissionByCreator(string $creatorUserId): float;

    /**
     * Obtener estadísticas de ventas por sala
     *
     * @param string $roomId
     * @return array{
     *     total_sales: float,
     *     total_rke_commission: float,
     *     total_room_commission: float,
     *     total_seller_commission: float,
     *     total_prize_pool: float,
     *     by_currency: array
     * }
     */
    public function getSalesStatsByRoom(string $roomId): array;

    /**
     * Obtener estadísticas de ventas por creador
     *
     * @param string $creatorUserId
     * @return array{
     *     total_sales: float,
     *     total_rke_commission: float,
     *     total_room_commission: float,
     *     total_seller_commission: float,
     *     total_prize_pool: float,
     *     room_count: int,
     *     by_currency: array
     * }
     */
    public function getSalesStatsByCreator(string $creatorUserId): array;

    /**
     * Obtener ventas con mayor volumen
     *
     * @param int $limit
     * @return Collection<int, BingoCreatorSale>
     */
    public function getTopSalesByVolume(int $limit = 10): Collection;

    /**
     * Obtener creadores con mayores ventas
     *
     * @param int $limit
     * @return Collection
     */
    public function getTopCreatorsBySales(int $limit = 10): Collection;

    /**
     * Obtener salas con mayores ventas
     *
     * @param int $limit
     * @return Collection
     */
    public function getTopRoomsBySales(int $limit = 10): Collection;

    /**
     * Crear o actualizar una venta
     *
     * @param string $roomId
     * @param string $creatorUserId
     * @param string $currency
     * @param float $amount
     * @param float|null $sellerCommission
     * @return BingoCreatorSale
     */
    public function updateOrCreateSale(string $roomId, string $creatorUserId, string $currency, float $amount, ?float $sellerCommission = null): BingoCreatorSale;

    /**
     * Incrementar monto de venta existente
     *
     * @param string $saleId
     * @param float $amount
     * @param float $rkeCommission
     * @param float $roomCommission
     * @param float $sellerCommission
     * @param float $prizePoolAmount
     * @return bool
     */
    public function incrementSaleAmounts(string $saleId, float $amount, float $rkeCommission, float $roomCommission, float $sellerCommission, float $prizePoolAmount): bool;

    /**
     * Obtener ventas por rango de fechas
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection<int, BingoCreatorSale>
     */
    public function getByDateRange(string $startDate, string $endDate): Collection;

    /**
     * Obtener ventas por rango de montos
     *
     * @param float $minAmount
     * @param float $maxAmount
     * @return Collection<int, BingoCreatorSale>
     */
    public function getBySaleAmountRange(float $minAmount, float $maxAmount): Collection;

    /**
     * Obtener resumen diario de ventas
     *
     * @param string $date
     * @return array{
     *     total_sales: float,
     *     total_transactions: int,
     *     total_commission: float,
     *     total_prize_pool: float
     * }
     */
    public function getDailySummary(string $date): array;

    /**
     * Obtener resumen mensual de ventas
     *
     * @param int $year
     * @param int $month
     * @return array{
     *     total_sales: float,
     *     total_transactions: int,
     *     total_commission: float,
     *     total_prize_pool: float,
     *     daily_breakdown: array
     * }
     */
    public function getMonthlySummary(int $year, int $month): array;

    /**
     * Buscar ventas por término de búsqueda
     *
     * @param string $searchTerm
     * @return Collection<int, BingoCreatorSale>
     */
    public function searchSales(string $searchTerm): Collection;

    /**
     * Obtener ventas agrupadas por moneda
     *
     * @return Collection
     */
    public function getSalesGroupedByCurrency(): Collection;

    /**
     * Obtener promedio de comisiones por tipo
     *
     * @return array{
     *     avg_rke_commission: float,
     *     avg_room_commission: float,
     *     avg_seller_commission: float
     * }
     */
    public function getAverageCommissions(): array;

    /**
     * Verificar integridad de datos de venta
     *
     * @param string $saleId
     * @return bool
     */
    public function validateSaleIntegrity(string $saleId): bool;
}
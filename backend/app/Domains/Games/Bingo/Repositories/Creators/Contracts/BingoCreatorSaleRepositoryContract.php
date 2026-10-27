<?php

namespace App\Domains\Games\Bingo\Repositories\Creators\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Creator\BingoCreatorSale;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de ventas de creadores de salas de bingo
 *
 * Define los métodos que debe implementar el repositorio para gestionar
 * las ventas y comisiones de los creadores de salas de bingo.
 *
 * @author Sistema PlayRKE
 *
 * @version 1.0
 */
interface BingoCreatorSaleRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Verificar si existe una venta para sala, creador y moneda específicos
     */
    public function existsByRoomCreatorAndCurrency(string $roomId, string $creatorUserId, ?string $currency = null) : bool;

    /**
     * Obtener venta por sala, creador y moneda
     */
    public function findByRoomCreatorAndCurrency(string $roomId, string $creatorUserId, string $currency) : ?BingoCreatorSale;

    /**
     * Obtener promedio de comisiones por tipo
     *
     * @return array{
     *     avg_rke_commission: float,
     *     avg_room_commission: float,
     *     avg_seller_commission: float
     * }
     */
    public function getAverageCommissions() : array;

    /**
     * Obtener todas las ventas por creador
     *
     * @return Collection<int, BingoCreatorSale>
     */
    public function getByCreatorUser(string $creatorUserId) : Collection;

    /**
     * Obtener todas las ventas por moneda
     *
     * @return Collection<int, BingoCreatorSale>
     */
    public function getByCurrency(string $currency) : Collection;

    /**
     * Obtener ventas por rango de fechas
     *
     * @return Collection<int, BingoCreatorSale>
     */
    public function getByDateRange(string $startDate, string $endDate) : Collection;

    /**
     * Obtener todas las ventas por sala
     *
     * @return Collection<int, BingoCreatorSale>
     */
    public function getByRoom(string $roomId) : Collection;

    /**
     * Obtener ventas por rango de montos
     *
     * @return Collection<int, BingoCreatorSale>
     */
    public function getBySaleAmountRange(float $minAmount, float $maxAmount) : Collection;

    /**
     * Obtener resumen diario de ventas
     *
     * @return array{
     *     total_sales: float,
     *     total_transactions: int,
     *     total_commission: float,
     *     total_prize_pool: float
     * }
     */
    public function getDailySummary(string $date) : array;

    /**
     * Obtener resumen mensual de ventas
     *
     * @return array{
     *     total_sales: float,
     *     total_transactions: int,
     *     total_commission: float,
     *     total_prize_pool: float,
     *     daily_breakdown: array
     * }
     */
    public function getMonthlySummary(int $year, int $month) : array;

    /**
     * Obtener total del premio acumulado por sala y moneda
     */
    public function getPrizePoolAmountByRoomAndCurrency(string $roomId, string $currency) : float;

    /**
     * Obtener ventas agrupadas por moneda
     */
    public function getSalesGroupedByCurrency() : Collection;

    /**
     * Obtener estadísticas de ventas por creador
     *
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
    public function getSalesStatsByCreator(string $creatorUserId) : array;

    /**
     * Obtener estadísticas de ventas por sala
     *
     * @return array{
     *     total_sales: float,
     *     total_rke_commission: float,
     *     total_room_commission: float,
     *     total_seller_commission: float,
     *     total_prize_pool: float,
     *     by_currency: array
     * }
     */
    public function getSalesStatsByRoom(string $roomId) : array;

    /**
     * Obtener creadores con mayores ventas
     */
    public function getTopCreatorsBySales(int $limit = 10) : Collection;

    /**
     * Obtener salas con mayores ventas
     */
    public function getTopRoomsBySales(int $limit = 10) : Collection;

    /**
     * Obtener ventas con mayor volumen
     *
     * @return Collection<int, BingoCreatorSale>
     */
    public function getTopSalesByVolume(int $limit = 10) : Collection;

    /**
     * Obtener total de comisiones RKE por creador
     */
    public function getTotalRkeCommissionByCreator(string $creatorUserId) : float;

    /**
     * Obtener total de comisiones RKE por sala
     */
    public function getTotalRkeCommissionByRoom(string $roomId) : float;

    /**
     * Obtener total de comisiones de sala por creador
     */
    public function getTotalRoomCommissionByCreator(string $creatorUserId) : float;

    /**
     * Obtener total de comisiones de sala por sala
     */
    public function getTotalRoomCommissionByRoom(string $roomId) : float;

    /**
     * Obtener total de ventas por creador
     */
    public function getTotalSalesByCreator(string $creatorUserId) : float;

    /**
     * Obtener total de ventas por sala
     */
    public function getTotalSalesByRoom(string $roomId) : float;

    /**
     * Obtener total de comisiones de vendedor por creador
     */
    public function getTotalSellerCommissionByCreator(string $creatorUserId) : float;

    /**
     * Obtener total de comisiones de vendedor por sala
     */
    public function getTotalSellerCommissionByRoom(string $roomId) : float;

    /**
     * Incrementar monto de venta existente
     */
    public function incrementSaleAmounts(string $saleId, float $amount, float $rkeCommission, float $roomCommission, float $sellerCommission, float $prizePoolAmount) : bool;

    /**
     * Buscar ventas por término de búsqueda
     *
     * @return Collection<int, BingoCreatorSale>
     */
    public function searchSales(string $searchTerm) : Collection;

    /**
     * Crear o actualizar una venta
     */
    public function updateOrCreateSale(string $roomId, string $creatorUserId, string $currency, float $amount, ?float $sellerCommission = null) : BingoCreatorSale;

    /**
     * Verificar integridad de datos de venta
     */
    public function validateSaleIntegrity(string $saleId) : bool;
}

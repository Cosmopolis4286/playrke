<?php

namespace App\Repositories\Bingo\Creators;

use App\Models\Bingo\Creators\BingoCreatorSale;
use App\Models\Bingo\Rooms\Config\BingoRoomConfig;
use App\Repositories\BaseRepository;
use App\Repositories\Bingo\Creators\Contracts\BingoCreatorSaleRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para gestionar ventas de creadores de salas de bingo
 *
 * Implementa todas las operaciones de consulta y manipulación de datos
 * para las ventas y comisiones de creadores de salas de bingo.
 *
 * @package App\Repositories\Bingo\Creators
 * @author Sistema PlayRKE
 * @version 1.0
 */
class BingoCreatorSaleRepository extends BaseRepository implements BingoCreatorSaleRepositoryContract
{
    /**
     * Constructor del repositorio
     *
     * @param BingoCreatorSale $model
     */
    public function __construct(BingoCreatorSale $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtener venta por sala, creador y moneda
     *
     * @param string $roomId
     * @param string $creatorUserId
     * @param string $currency
     * @return BingoCreatorSale|null
     */
    public function findByRoomCreatorAndCurrency(string $roomId, string $creatorUserId, string $currency): ?BingoCreatorSale
    {
        return $this->model->where('room', $roomId)
            ->where('creator_user_id', $creatorUserId)
            ->where('currency', $currency)
            ->first();
    }

    /**
     * Verificar si existe una venta para sala, creador y moneda específicos
     *
     * @param string $roomId
     * @param string $creatorUserId
     * @param string|null $currency
     * @return bool
     */
    public function existsByRoomCreatorAndCurrency(string $roomId, string $creatorUserId, ?string $currency = null): bool
    {
        $query = $this->model->where('room', $roomId)
            ->where('creator_user_id', $creatorUserId);

        if ($currency) {
            $query->where('currency', $currency);
        }

        return $query->exists();
    }

    /**
     * Obtener todas las ventas por sala
     *
     * @param string $roomId
     * @return Collection<int, BingoCreatorSale>
     */
    public function getByRoom(string $roomId): Collection
    {
        return $this->model->where('room', $roomId)->get();
    }

    /**
     * Obtener todas las ventas por creador
     *
     * @param string $creatorUserId
     * @return Collection<int, BingoCreatorSale>
     */
    public function getByCreatorUser(string $creatorUserId): Collection
    {
        return $this->model->where('creator_user_id', $creatorUserId)->get();
    }

    /**
     * Obtener todas las ventas por moneda
     *
     * @param string $currency
     * @return Collection<int, BingoCreatorSale>
     */
    public function getByCurrency(string $currency): Collection
    {
        return $this->model->where('currency', $currency)->get();
    }

    /**
     * Obtener total del premio acumulado por sala y moneda
     *
     * @param string $roomId
     * @param string $currency
     * @return float
     */
    public function getPrizePoolAmountByRoomAndCurrency(string $roomId, string $currency): float
    {
        return (float) $this->model->where('room', $roomId)
            ->where('currency', $currency)
            ->sum('prize_pool_amount');
    }

    /**
     * Obtener total de ventas por sala
     *
     * @param string $roomId
     * @return float
     */
    public function getTotalSalesByRoom(string $roomId): float
    {
        return (float) $this->model->where('room', $roomId)->sum('sale_amount');
    }

    /**
     * Obtener total de comisiones RKE por sala
     *
     * @param string $roomId
     * @return float
     */
    public function getTotalRkeCommissionByRoom(string $roomId): float
    {
        return (float) $this->model->where('room', $roomId)->sum('rke_commn');
    }

    /**
     * Obtener total de comisiones de sala por sala
     *
     * @param string $roomId
     * @return float
     */
    public function getTotalRoomCommissionByRoom(string $roomId): float
    {
        return (float) $this->model->where('room', $roomId)->sum('room_commn');
    }

    /**
     * Obtener total de comisiones de vendedor por sala
     *
     * @param string $roomId
     * @return float
     */
    public function getTotalSellerCommissionByRoom(string $roomId): float
    {
        return (float) $this->model->where('room', $roomId)->sum('seller_commn');
    }

    /**
     * Obtener total de ventas por creador
     *
     * @param string $creatorUserId
     * @return float
     */
    public function getTotalSalesByCreator(string $creatorUserId): float
    {
        return (float) $this->model->where('creator_user_id', $creatorUserId)->sum('sale_amount');
    }

    /**
     * Obtener total de comisiones RKE por creador
     *
     * @param string $creatorUserId
     * @return float
     */
    public function getTotalRkeCommissionByCreator(string $creatorUserId): float
    {
        return (float) $this->model->where('creator_user_id', $creatorUserId)->sum('rke_commn');
    }

    /**
     * Obtener total de comisiones de sala por creador
     *
     * @param string $creatorUserId
     * @return float
     */
    public function getTotalRoomCommissionByCreator(string $creatorUserId): float
    {
        return (float) $this->model->where('creator_user_id', $creatorUserId)->sum('room_commn');
    }

    /**
     * Obtener total de comisiones de vendedor por creador
     *
     * @param string $creatorUserId
     * @return float
     */
    public function getTotalSellerCommissionByCreator(string $creatorUserId): float
    {
        return (float) $this->model->where('creator_user_id', $creatorUserId)->sum('seller_commn');
    }

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
    public function getSalesStatsByRoom(string $roomId): array
    {
        $totalSales = $this->getTotalSalesByRoom($roomId);
        $totalRkeCommission = $this->getTotalRkeCommissionByRoom($roomId);
        $totalRoomCommission = $this->getTotalRoomCommissionByRoom($roomId);
        $totalSellerCommission = $this->getTotalSellerCommissionByRoom($roomId);
        $totalPrizePool = (float) $this->model->where('room', $roomId)->sum('prize_pool_amount');

        $byCurrency = $this->model->where('room', $roomId)
            ->selectRaw('currency, SUM(sale_amount) as total_sales, SUM(prize_pool_amount) as total_prize_pool')
            ->groupBy('currency')
            ->get()
            ->keyBy('currency')
            ->toArray();

        return [
            'total_sales' => $totalSales,
            'total_rke_commission' => $totalRkeCommission,
            'total_room_commission' => $totalRoomCommission,
            'total_seller_commission' => $totalSellerCommission,
            'total_prize_pool' => $totalPrizePool,
            'by_currency' => $byCurrency
        ];
    }

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
    public function getSalesStatsByCreator(string $creatorUserId): array
    {
        $totalSales = $this->getTotalSalesByCreator($creatorUserId);
        $totalRkeCommission = $this->getTotalRkeCommissionByCreator($creatorUserId);
        $totalRoomCommission = $this->getTotalRoomCommissionByCreator($creatorUserId);
        $totalSellerCommission = $this->getTotalSellerCommissionByCreator($creatorUserId);
        $totalPrizePool = (float) $this->model->where('creator_user_id', $creatorUserId)->sum('prize_pool_amount');
        $roomCount = $this->model->where('creator_user_id', $creatorUserId)->distinct('room')->count();

        $byCurrency = $this->model->where('creator_user_id', $creatorUserId)
            ->selectRaw('currency, SUM(sale_amount) as total_sales, SUM(prize_pool_amount) as total_prize_pool')
            ->groupBy('currency')
            ->get()
            ->keyBy('currency')
            ->toArray();

        return [
            'total_sales' => $totalSales,
            'total_rke_commission' => $totalRkeCommission,
            'total_room_commission' => $totalRoomCommission,
            'total_seller_commission' => $totalSellerCommission,
            'total_prize_pool' => $totalPrizePool,
            'room_count' => $roomCount,
            'by_currency' => $byCurrency
        ];
    }

    /**
     * Obtener ventas con mayor volumen
     *
     * @param int $limit
     * @return Collection<int, BingoCreatorSale>
     */
    public function getTopSalesByVolume(int $limit = 10): Collection
    {
        return $this->model->orderByDesc('sale_amount')->limit($limit)->get();
    }

    /**
     * Obtener creadores con mayores ventas
     *
     * @param int $limit
     * @return Collection
     */
    public function getTopCreatorsBySales(int $limit = 10): Collection
    {
        return $this->model->selectRaw('creator_user_id, SUM(sale_amount) as total_sales')
            ->groupBy('creator_user_id')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener salas con mayores ventas
     *
     * @param int $limit
     * @return Collection
     */
    public function getTopRoomsBySales(int $limit = 10): Collection
    {
        return $this->model->selectRaw('room, SUM(sale_amount) as total_sales')
            ->groupBy('room')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();
    }

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
    public function updateOrCreateSale(string $roomId, string $creatorUserId, string $currency, float $amount, ?float $sellerCommission = null): BingoCreatorSale
    {
        // Obtener configuración de la sala
        $roomConfig = BingoRoomConfig::where('room', $roomId)->first(['rke_commn', 'room_commn']);

        $rkeCommission = round($amount * (($roomConfig->rke_commn ?? 0) / 100), 3);
        $roomCommission = round($amount * (($roomConfig->room_commn ?? 0) / 100), 3);
        $sellerCommission = round($sellerCommission ?? 0, 3);
        $prizePool = round($amount - $rkeCommission - $roomCommission - $sellerCommission, 3);

        $sale = $this->model->firstOrCreate(
            [
                'room' => $roomId,
                'creator_user_id' => $creatorUserId,
                'currency' => $currency
            ],
            [
                'sale_amount' => $amount,
                'rke_commn' => $rkeCommission,
                'room_commn' => $roomCommission,
                'seller_commn' => $sellerCommission,
                'prize_pool_amount' => $prizePool
            ]
        );

        if (!$sale->wasRecentlyCreated && $amount > 0) {
            $sale->increment('sale_amount', $amount);
            $sale->increment('rke_commn', $rkeCommission);
            $sale->increment('room_commn', $roomCommission);
            $sale->increment('seller_commn', $sellerCommission);
            $sale->increment('prize_pool_amount', $prizePool);
        }

        return $sale;
    }

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
    public function incrementSaleAmounts(string $saleId, float $amount, float $rkeCommission, float $roomCommission, float $sellerCommission, float $prizePoolAmount): bool
    {
        return $this->model->where('id', $saleId)->update([
            'sale_amount' => DB::raw("sale_amount + {$amount}"),
            'rke_commn' => DB::raw("rke_commn + {$rkeCommission}"),
            'room_commn' => DB::raw("room_commn + {$roomCommission}"),
            'seller_commn' => DB::raw("seller_commn + {$sellerCommission}"),
            'prize_pool_amount' => DB::raw("prize_pool_amount + {$prizePoolAmount}")
        ]) > 0;
    }

    /**
     * Obtener ventas por rango de fechas
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection<int, BingoCreatorSale>
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('created_at', [$startDate, $endDate])->get();
    }

    /**
     * Obtener ventas por rango de montos
     *
     * @param float $minAmount
     * @param float $maxAmount
     * @return Collection<int, BingoCreatorSale>
     */
    public function getBySaleAmountRange(float $minAmount, float $maxAmount): Collection
    {
        return $this->model->whereBetween('sale_amount', [$minAmount, $maxAmount])->get();
    }

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
    public function getDailySummary(string $date): array
    {
        $summary = $this->model->whereDate('created_at', $date)
            ->selectRaw('
                SUM(sale_amount) as total_sales,
                COUNT(*) as total_transactions,
                SUM(rke_commn + room_commn + seller_commn) as total_commission,
                SUM(prize_pool_amount) as total_prize_pool
            ')
            ->first();

        return [
            'total_sales' => (float) ($summary->total_sales ?? 0),
            'total_transactions' => (int) ($summary->total_transactions ?? 0),
            'total_commission' => (float) ($summary->total_commission ?? 0),
            'total_prize_pool' => (float) ($summary->total_prize_pool ?? 0)
        ];
    }

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
    public function getMonthlySummary(int $year, int $month): array
    {
        $monthlySummary = $this->model->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->selectRaw('
                SUM(sale_amount) as total_sales,
                COUNT(*) as total_transactions,
                SUM(rke_commn + room_commn + seller_commn) as total_commission,
                SUM(prize_pool_amount) as total_prize_pool
            ')
            ->first();

        $dailyBreakdown = $this->model->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->selectRaw('
                DATE(created_at) as date,
                SUM(sale_amount) as daily_sales,
                COUNT(*) as daily_transactions
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        return [
            'total_sales' => (float) ($monthlySummary->total_sales ?? 0),
            'total_transactions' => (int) ($monthlySummary->total_transactions ?? 0),
            'total_commission' => (float) ($monthlySummary->total_commission ?? 0),
            'total_prize_pool' => (float) ($monthlySummary->total_prize_pool ?? 0),
            'daily_breakdown' => $dailyBreakdown
        ];
    }

    /**
     * Buscar ventas por término de búsqueda
     *
     * @param string $searchTerm
     * @return Collection<int, BingoCreatorSale>
     */
    public function searchSales(string $searchTerm): Collection
    {
        return $this->model->where(function ($query) use ($searchTerm) {
            $query->where('room', 'like', "%{$searchTerm}%")
                ->orWhere('creator_user_id', 'like', "%{$searchTerm}%")
                ->orWhere('currency', 'like', "%{$searchTerm}%");
        })->get();
    }

    /**
     * Obtener ventas agrupadas por moneda
     *
     * @return Collection
     */
    public function getSalesGroupedByCurrency(): Collection
    {
        return $this->model->selectRaw('
                currency,
                SUM(sale_amount) as total_sales,
                COUNT(*) as transaction_count,
                AVG(sale_amount) as avg_sale_amount
            ')
            ->groupBy('currency')
            ->orderByDesc('total_sales')
            ->get();
    }

    /**
     * Obtener promedio de comisiones por tipo
     *
     * @return array{
     *     avg_rke_commission: float,
     *     avg_room_commission: float,
     *     avg_seller_commission: float
     * }
     */
    public function getAverageCommissions(): array
    {
        $averages = $this->model->selectRaw('
                AVG(rke_commn) as avg_rke_commission,
                AVG(room_commn) as avg_room_commission,
                AVG(seller_commn) as avg_seller_commission
            ')
            ->first();

        return [
            'avg_rke_commission' => (float) ($averages->avg_rke_commission ?? 0),
            'avg_room_commission' => (float) ($averages->avg_room_commission ?? 0),
            'avg_seller_commission' => (float) ($averages->avg_seller_commission ?? 0)
        ];
    }

    /**
     * Verificar integridad de datos de venta
     *
     * @param string $saleId
     * @return bool
     */
    public function validateSaleIntegrity(string $saleId): bool
    {
        $sale = $this->find($saleId);

        if (!$sale) {
            return false;
        }

        $calculatedTotal = $sale->rke_commn + $sale->room_commn + $sale->seller_commn + $sale->prize_pool_amount;
        $tolerance = 0.01; // Tolerancia para errores de redondeo

        return abs($sale->sale_amount - $calculatedTotal) <= $tolerance;
    }
}
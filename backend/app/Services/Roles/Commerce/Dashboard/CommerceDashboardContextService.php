<?php

namespace App\Services\Roles\Commerce\Dashboard;

use App\DTOs\Roles\Commerce\Dashboard\CommerceDashboardContextDTO;
use App\Contracts\WalletRepositoryInterface;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\WalletMovement;
use App\Services\Roles\Dashboard\Concerns\BuildsSalesTrend;
use App\Services\Roles\Dashboard\Concerns\BuildsWeeklyTrend;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Contexto del dashboard de Commerce (comercializadora) — dueño de las
 * salas que crea (BingoRoom.creator_user_id = su user_id) y de la red de
 * vendedores/agencias que operan bajo su cuenta.
 */
final class CommerceDashboardContextService
{
    use BuildsSalesTrend;
    use BuildsWeeklyTrend;

    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
    ) {}

    public function dashboardContext(): CommerceDashboardContextDTO
    {
        $user   = Auth::user();
        $userId = (string) $user?->getKey();
        $now    = Carbon::now();
        $period = ucfirst($now->translatedFormat('F Y'));

        $commerce = Commerce::where('user_id', $userId)->first(['id', 'commission']);

        return new CommerceDashboardContextDTO(
            userName:           $user?->first_name ?? $user?->username ?? 'Comercio',
            stats:              $this->buildStats($userId, $commerce, $now),
            recentActivity:     $this->buildRecentActivity($userId),
            entityCounts:       $this->buildEntityCounts($commerce),
            salesTrend:         $this->buildSalesTrend(scope: fn ($q) => $q->where('creator_user_id', $userId)),
            salesBySeller:      $this->buildSalesBySeller($userId, $now),
            wallets:            $this->buildWallets($userId),
            configuration:      $this->buildConfiguration($commerce),
            pendingWalletCount: WalletMovement::forUser($userId)->where('status', WalletMovement::STATUS_PENDING)->count(),
            period:             $period,
        );
    }

    // -------------------------------------------------------------------------
    // Stats principales
    // -------------------------------------------------------------------------

    private function buildStats(string $userId, ?Commerce $commerce, Carbon $now): array
    {
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfDay   = $now->copy()->startOfDay();

        $lotsThisMonth = BingoCardLot::where('user_id', $userId)
            ->where('created_at', '>=', $startOfMonth);

        $totalSalesThisMonth = (clone $lotsThisMonth)->sum('sale_amount');
        $cardsSoldToday      = BingoCard::where('creator_user_id', $userId)
            ->where('created_at', '>=', $startOfDay)
            ->count();
        $activeRooms = BingoRoom::active()->where('creator_user_id', $userId)->count();
        $pendingPayouts = WalletMovement::forUser($userId)
            ->where('status', WalletMovement::STATUS_PENDING)
            ->count();

        return [
            'totalSales' => array_merge([
                'label'       => 'Ventas del mes',
                'value'       => number_format((float) $totalSalesThisMonth, 2, ',', '.'),
                'icon'        => 'fas fa-shopping-cart',
                'variant'     => 'primary',
                'href'        => '/bingo/sales-report',
            ], $this->buildWeeklyTrend(BingoCardLot::where('user_id', $userId))),
            'totalRevenue' => [
                'label'   => 'Cartones vendidos hoy',
                'value'   => $cardsSoldToday,
                'icon'    => 'fas fa-ticket-alt',
                'variant' => 'success',
            ],
            'activeGames' => [
                'label'   => 'Salas activas',
                'value'   => $activeRooms,
                'icon'    => 'fas fa-dice',
                'variant' => 'info',
                'href'    => '/bingo-rooms',
            ],
            'pendingPayouts' => [
                'label'   => 'Movimientos pendientes',
                'value'   => $pendingPayouts,
                'icon'    => 'fas fa-clock',
                'variant' => 'warning',
                'href'    => '/wallet-movements',
            ],
            'commission' => [
                'label'       => 'Mi comisión',
                'value'       => $commerce ? number_format((float) $commerce->commission, 2) . '%' : '—',
                'icon'        => 'fas fa-percentage',
                'variant'     => 'secondary',
                'description' => 'Tasa configurada',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Ventas agrupadas por vendedor (top 10, este mes)
    // -------------------------------------------------------------------------

    private function buildSalesBySeller(string $userId, Carbon $now): array
    {
        $startOfMonth = $now->copy()->startOfMonth();

        return BingoCardLot::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $startOfMonth)
            ->select([
                'seller_user_id',
                DB::raw('SUM(sale_amount) as total_sales'),
                DB::raw('SUM(card_count) as total_cards'),
            ])
            ->groupBy('seller_user_id')
            ->orderByDesc(DB::raw('SUM(sale_amount)'))
            ->limit(10)
            ->with('seller:id,first_name,last_name,username')
            ->get()
            ->map(function (BingoCardLot $row) {
                $sellerName = $row->seller
                    ? trim("{$row->seller->first_name} {$row->seller->last_name}") ?: $row->seller->username
                    : 'Venta directa';

                return [
                    'seller_user_id' => $row->seller_user_id ? (string) $row->seller_user_id : null,
                    'seller_name'    => $sellerName,
                    'total_sales'    => round((float) $row->total_sales, 2),
                    'total_cards'    => (int) $row->total_cards,
                ];
            })
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Billeteras propias (saldo por moneda)
    // -------------------------------------------------------------------------

    private function buildWallets(string $userId): array
    {
        return $this->walletRepository->getActiveWalletsByUser($userId, ['id', 'currency_id', 'balance', 'bonus_balance'])
            ->load('currency:id,iso_code,symbol')
            ->map(fn ($wallet) => [
                'currency_code' => $wallet->currency?->iso_code ?? '—',
                'symbol'        => $wallet->currency?->symbol ?? '',
                'balance'       => (float) $wallet->balance,
                'bonus_balance' => (float) $wallet->bonus_balance,
            ])
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Monedas y juegos habilitados
    // -------------------------------------------------------------------------

    private function buildConfiguration(?Commerce $commerce): array
    {
        if (!$commerce) {
            return ['currencies' => [], 'games' => []];
        }

        return [
            'currencies' => $commerce->currenciesActive()->get(['currencies.id', 'iso_code', 'symbol'])
                ->map(fn ($c) => ['code' => $c->iso_code, 'symbol' => $c->symbol])
                ->values()
                ->all(),
            'games' => $commerce->gamesActive()->get(['games.id', 'name'])
                ->pluck('name')
                ->values()
                ->all(),
        ];
    }

    // -------------------------------------------------------------------------
    // Red propia: vendedores directos + agencias/sucursales
    // -------------------------------------------------------------------------

    private function buildEntityCounts(?Commerce $commerce): array
    {
        if (!$commerce) {
            return [];
        }

        // sellers() solo resuelve reporte directo (manager_user_id); los
        // vendedores que reportan vía una Subsidiary no tienen commerce_id
        // propio en `sellers` (ver Seller::resolveCommerce()), así que se
        // cuentan por separado a través de las subsidiarias del commerce.
        $directSellers = $commerce->sellers()->count();
        $subsidiaries  = $commerce->subsidiaries()->count();
        $subsidiarySellers = $commerce->subsidiaries()
            ->withCount('sellers')
            ->get()
            ->sum('sellers_count');

        return [
            array_merge([
                'label' => 'Vendedores directos', 'value' => $directSellers, 'icon' => 'fas fa-user-tie',
                'variant' => 'success', 'href' => '/admin/sellers',
            ], $this->buildWeeklyTrend(Seller::where('manager_user_id', $commerce->user_id))),
            [
                'label' => 'Agencias/Sucursales', 'value' => $subsidiaries, 'icon' => 'fas fa-code-branch',
                'variant' => 'info',
            ],
            [
                'label' => 'Vendedores en agencias', 'value' => $subsidiarySellers, 'icon' => 'fas fa-users',
                'variant' => 'secondary',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Actividad reciente: ventas de la red propia
    // -------------------------------------------------------------------------

    private function buildRecentActivity(string $userId): array
    {
        return BingoCardLot::where('user_id', $userId)
            ->with(['room:id,uuid_code', 'currency:id,symbol,iso_code', 'seller:id,first_name,last_name,username'])
            ->latest()
            ->limit(10)
            ->get(['id', 'room_id', 'seller_user_id', 'currency_id', 'card_count', 'sale_amount', 'status', 'created_at'])
            ->map(function (BingoCardLot $lot) {
                $sellerName = $lot->seller
                    ? trim("{$lot->seller->first_name} {$lot->seller->last_name}") ?: $lot->seller->username
                    : 'Venta directa';

                return [
                    'id'          => (string) $lot->getKey(),
                    'type'        => 'sale',
                    'title'       => "{$lot->card_count} cartón(es) vendidos",
                    'description' => "Sala {$lot->room?->uuid_code} · {$sellerName}",
                    'timestamp'   => $lot->created_at->toIso8601String(),
                    'actor'       => $sellerName,
                    'meta'        => [
                        'status'   => strtolower((string) $lot->status),
                        'price'    => $lot->currency?->formatAmount((float) $lot->sale_amount) ?? number_format((float) $lot->sale_amount, 2, ',', '.'),
                        'currency' => $lot->currency?->iso_code ?? '',
                    ],
                ];
            })
            ->all();
    }
}

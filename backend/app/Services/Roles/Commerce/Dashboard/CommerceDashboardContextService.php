<?php

namespace App\Services\Roles\Commerce\Dashboard;

use App\DTOs\Roles\Commerce\Dashboard\CommerceDashboardContextDTO;
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

/**
 * Contexto del dashboard de Commerce (comercializadora) — dueño de las
 * salas que crea (BingoRoom.creator_user_id = su user_id) y de la red de
 * vendedores/agencias que operan bajo su cuenta.
 */
final class CommerceDashboardContextService
{
    use BuildsSalesTrend;
    use BuildsWeeklyTrend;

    public function dashboardContext(): CommerceDashboardContextDTO
    {
        $user   = Auth::user();
        $userId = (string) $user?->getKey();
        $now    = Carbon::now();
        $period = ucfirst($now->translatedFormat('F Y'));

        $commerce = Commerce::where('user_id', $userId)->first(['id', 'commission']);

        return new CommerceDashboardContextDTO(
            userName:           $user?->first_name ?? $user?->username ?? 'Comercio',
            stats:              $this->buildStats($userId, $now),
            recentActivity:     $this->buildRecentActivity($userId),
            entityCounts:       $this->buildEntityCounts($commerce),
            salesTrend:         $this->buildSalesTrend(scope: fn ($q) => $q->where('creator_user_id', $userId)),
            pendingWalletCount: WalletMovement::forUser($userId)->where('status', WalletMovement::STATUS_PENDING)->count(),
            period:             $period,
        );
    }

    // -------------------------------------------------------------------------
    // Stats principales
    // -------------------------------------------------------------------------

    private function buildStats(string $userId, Carbon $now): array
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

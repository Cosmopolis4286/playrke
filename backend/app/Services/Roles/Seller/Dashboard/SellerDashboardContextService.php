<?php

namespace App\Services\Roles\Seller\Dashboard;

use App\DTOs\Roles\Seller\Dashboard\SellerDashboardContextDTO;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Models\Pivots\Roles\Seller\SellerRoomLimits;
use App\Models\Roles\Seller;
use App\Services\Roles\Dashboard\Concerns\BuildsSalesTrend;
use App\Services\Roles\Seller\SellerRankingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Contexto del dashboard de Seller (vendedor) — ventas propias
 * (BingoCard/BingoCardLot.seller_user_id = su user_id), sin importar a
 * través de qué Commerce/Subsidiary reporta.
 */
final class SellerDashboardContextService
{
    use BuildsSalesTrend;

    public function __construct(
        private readonly SellerRankingService $sellerRanking,
    ) {
    }

    public function dashboardContext(): SellerDashboardContextDTO
    {
        $user   = Auth::user();
        $userId = (string) $user?->getKey();
        $now    = Carbon::now();
        $period = ucfirst($now->translatedFormat('F Y'));

        $seller = Seller::where('user_id', $userId)->first(['id', 'commission', 'manager_user_id', 'subsidiary_id']);

        return new SellerDashboardContextDTO(
            userName:        $user?->first_name ?? $user?->username ?? 'Vendedor',
            commission:      $seller ? number_format((float) $seller->commission, 2) : '0.00',
            stats:           $this->buildStats($userId, $seller, $now),
            recentActivity:  $this->buildRecentActivity($userId),
            reportsTo:       $this->buildReportsTo($seller),
            roomCommissions: $this->buildRoomCommissions($seller),
            salesTrend:      $this->buildSalesTrend(scope: fn ($q) => $q->where('seller_user_id', $userId)),
            period:          $period,
            ranking:         $this->sellerRanking->getSellerStanding($userId),
        );
    }

    // -------------------------------------------------------------------------
    // A quién reporta: Commerce directo o Agencia/Subsidiary intermedia
    // -------------------------------------------------------------------------

    private function buildReportsTo(?Seller $seller): ?array
    {
        if (!$seller) {
            return null;
        }

        $commerce = $seller->resolveCommerce();

        if (!$commerce) {
            return null;
        }

        return [
            'name'    => $seller->subsidiary_id ? $seller->manager_name : $commerce->name,
            'type'    => $seller->subsidiary_id ? 'subsidiary' : 'commerce',
            'commerce_name' => $commerce->name,
        ];
    }

    // -------------------------------------------------------------------------
    // Comisiones especiales configuradas por sala (sellers_room_limits)
    // -------------------------------------------------------------------------

    private function buildRoomCommissions(?Seller $seller): array
    {
        if (!$seller) {
            return [];
        }

        return SellerRoomLimits::where('seller_id', $seller->id)
            ->with('room:id,uuid_code')
            ->latest('created_at')
            ->limit(10)
            ->get(['id', 'room_id', 'commission'])
            ->map(fn (SellerRoomLimits $limit) => [
                'room_id'    => (string) $limit->room_id,
                'room_name'  => $limit->room?->uuid_code ?? ('…' . substr((string) $limit->room_id, -6)),
                'commission' => (float) $limit->commission,
            ])
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Stats principales
    // -------------------------------------------------------------------------

    private function buildStats(string $userId, ?Seller $seller, Carbon $now): array
    {
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfDay   = $now->copy()->startOfDay();

        $cardsSoldToday = BingoCard::where('seller_user_id', $userId)
            ->where('created_at', '>=', $startOfDay)
            ->count();

        $cardsSoldThisMonth = BingoCard::where('seller_user_id', $userId)
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $commissionThisMonth = BingoCardLot::where('seller_user_id', $userId)
            ->where('created_at', '>=', $startOfMonth)
            ->sum('commission_amount');

        $winnersToday = BingoCard::where('seller_user_id', $userId)
            ->winners()
            ->where('updated_at', '>=', $startOfDay)
            ->count();

        return [
            'cartonsSoldToday' => [
                'label'   => 'Cartones vendidos hoy',
                'value'   => $cardsSoldToday,
                'icon'    => 'fas fa-ticket-alt',
                'variant' => 'primary',
                'href'    => '/bingo/cards-sold',
            ],
            'cartonsThisMonth' => [
                'label'   => 'Cartones este mes',
                'value'   => $cardsSoldThisMonth,
                'icon'    => 'fas fa-calendar-check',
                'variant' => 'success',
                'href'    => '/bingo/cards-sold',
            ],
            'commissionThisMonth' => [
                'label'       => 'Comisión del mes',
                'value'       => number_format((float) $commissionThisMonth, 2, ',', '.'),
                'icon'        => 'fas fa-percentage',
                'variant'     => 'info',
                'description' => $seller ? "Tasa: {$seller->commission}%" : null,
                'href'        => '/bingo/sales-report',
            ],
            'winnersToday' => [
                'label'   => 'Ganadores hoy',
                'value'   => $winnersToday,
                'icon'    => 'fas fa-trophy',
                'variant' => 'warning',
                'href'    => '/bingo/winners/private/today',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Actividad reciente: lotes de venta propios
    // -------------------------------------------------------------------------

    private function buildRecentActivity(string $userId): array
    {
        return BingoCardLot::where('seller_user_id', $userId)
            ->with(['room:id,uuid_code', 'currency:id,symbol,iso_code'])
            ->latest()
            ->limit(10)
            ->get(['id', 'room_id', 'currency_id', 'card_count', 'sale_amount', 'status', 'created_at'])
            ->map(fn (BingoCardLot $lot) => [
                'id'          => (string) $lot->getKey(),
                'type'        => 'sale',
                'title'       => "{$lot->card_count} cartón(es) vendidos",
                'description' => "Sala {$lot->room?->uuid_code}",
                'timestamp'   => $lot->created_at->toIso8601String(),
                'meta'        => [
                    'status'   => strtolower((string) $lot->status),
                    'price'    => $lot->currency?->formatAmount((float) $lot->sale_amount) ?? number_format((float) $lot->sale_amount, 2, ',', '.'),
                    'currency' => $lot->currency?->iso_code ?? '',
                ],
            ])
            ->all();
    }
}

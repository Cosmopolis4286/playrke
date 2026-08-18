<?php

namespace App\Services\Roles\Seller\Dashboard;

use App\DTOs\Roles\Seller\Dashboard\SellerDashboardContextDTO;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Models\Roles\Seller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Contexto del dashboard de Seller (vendedor) — ventas propias
 * (BingoCard/BingoCardLot.seller_user_id = su user_id), sin importar a
 * través de qué Commerce/Subsidiary reporta.
 */
final class SellerDashboardContextService
{
    public function dashboardContext(): SellerDashboardContextDTO
    {
        $user   = Auth::user();
        $userId = (string) $user?->getKey();
        $now    = Carbon::now();
        $period = ucfirst($now->translatedFormat('F Y'));

        $seller = Seller::where('user_id', $userId)->first(['id', 'commission']);

        return new SellerDashboardContextDTO(
            userName:       $user?->first_name ?? $user?->username ?? 'Vendedor',
            commission:     $seller ? number_format((float) $seller->commission, 2) : '0.00',
            stats:          $this->buildStats($userId, $seller, $now),
            recentActivity: $this->buildRecentActivity($userId),
            period:         $period,
        );
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

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
            salesTrend:      $this->buildSalesTrend(scope: fn ($q) => $q
                ->where('seller_user_id', $userId)
                ->where('status', '!=', BingoCard::STATUS_CANCELLED)),
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

        // Cartones anulados (pago rechazado / sala cancelada) no son venta.
        $soldCards = fn () => BingoCard::where('seller_user_id', $userId)
            ->where('status', '!=', BingoCard::STATUS_CANCELLED);

        $cardsSoldToday     = $soldCards()->where('created_at', '>=', $startOfDay)->count();
        $cardsSoldThisMonth = $soldCards()->where('created_at', '>=', $startOfMonth)->count();

        // Comparación justa: mismos días del mes anterior (1 → hoy-1 mes).
        $prevStart = $startOfMonth->copy()->subMonthNoOverflow();
        $prevEnd   = $now->copy()->subMonthNoOverflow();
        $cardsSamePeriodLastMonth = $soldCards()->whereBetween('created_at', [$prevStart, $prevEnd])->count();

        $commission = $this->buildCommissionByCurrency($userId, $startOfMonth);

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
            'cartonsThisMonth' => array_merge([
                'label'   => 'Cartones este mes',
                'value'   => $cardsSoldThisMonth,
                'icon'    => 'fas fa-calendar-check',
                'variant' => 'success',
                'href'    => '/bingo/cards-sold',
            ], $this->trendAgainst($cardsSoldThisMonth, $cardsSamePeriodLastMonth)),
            'commissionThisMonth' => [
                'label'       => 'Comisión del mes',
                'value'       => $commission['value'],
                'icon'        => 'fas fa-percentage',
                'variant'     => 'info',
                'description' => trim(implode(' · ', array_filter([
                    $commission['pending'],
                    $commission['others'],
                    $seller ? "Tasa {$seller->commission}%" : null,
                ]))),
                'href'        => '/bingo/sales-report',
            ],
            'winnersToday' => [
                'label'   => 'Ganadores hoy',
                'value'   => $winnersToday,
                'icon'    => 'fas fa-trophy',
                'variant' => 'warning',
                'href'    => '/bingo/winners',
            ],
        ];
    }

    /**
     * Comisión del mes POR MONEDA, separando la confirmada (pago aprobado) de
     * la que está en revisión. Antes se sumaba commission_amount de todas las
     * monedas como un solo número (bolívares + pesos + dólares) e incluía
     * lotes reembolsados/rechazados.
     *
     * @return array{value: string, pending: ?string, others: ?string}
     */
    private function buildCommissionByCurrency(string $userId, Carbon $since): array
    {
        $rows = BingoCardLot::query()
            ->where('bingo_card_lots.seller_user_id', $userId)
            ->where('bingo_card_lots.created_at', '>=', $since)
            ->where('bingo_card_lots.status', '!=', 'REFUNDED')
            ->leftJoin('bingo_card_payments as p', 'p.card_lot_id', '=', 'bingo_card_lots.id')
            ->where(fn ($q) => $q->whereNull('p.payment_status')->orWhereIn('p.payment_status', ['PENDING', 'APPROVED']))
            ->groupBy('bingo_card_lots.currency_id')
            ->selectRaw("bingo_card_lots.currency_id,
                SUM(CASE WHEN p.payment_status = 'PENDING' THEN bingo_card_lots.commission_amount ELSE 0 END) AS pending_sum,
                SUM(CASE WHEN p.payment_status = 'PENDING' THEN 0 ELSE bingo_card_lots.commission_amount END) AS confirmed_sum")
            ->toBase()
            ->get()
            ->sortByDesc(fn ($r) => (float) $r->confirmed_sum + (float) $r->pending_sum)
            ->values();

        if ($rows->isEmpty()) {
            return ['value' => '0,00', 'pending' => null, 'others' => null];
        }

        $currencies = \App\Models\Currency::whereIn('id', $rows->pluck('currency_id'))->get()->keyBy('id');
        $format = function ($currencyId, float $amount) use ($currencies): string {
            $currency = $currencies->get($currencyId);

            return $currency
                ? trim(($currency->symbol ?? '') . ' ' . $currency->formatAmount($amount))
                : number_format($amount, 2, ',', '.');
        };

        $primary = $rows->first();

        return [
            'value'   => $format($primary->currency_id, (float) $primary->confirmed_sum),
            'pending' => (float) $primary->pending_sum > 0
                ? $format($primary->currency_id, (float) $primary->pending_sum) . ' en revisión'
                : null,
            'others'  => $rows->count() > 1 ? '+' . ($rows->count() - 1) . ' moneda(s) más' : null,
        ];
    }

    /**
     * Variación % contra el mismo tramo del mes anterior, en el formato de
     * DashboardStatCard (trend + trendValue). Sin base de comparación → nada.
     *
     * @return array{trend?: string, trendValue?: float}
     */
    private function trendAgainst(int $current, int $previous): array
    {
        if ($previous === 0) {
            return [];
        }

        $change = round((($current - $previous) / $previous) * 100, 1);

        return [
            'trend'      => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
            'trendValue' => abs($change),
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

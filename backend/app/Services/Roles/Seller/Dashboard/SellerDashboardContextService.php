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
            paymentReview:   $this->buildPaymentReview($userId),
            closingRooms:    $this->buildClosingRooms($seller),
            purchaseCodes:   $this->buildPurchaseCodes($userId),
        );
    }

    // -------------------------------------------------------------------------
    // Pagos en revisión / rechazados (acción pendiente del vendedor)
    // -------------------------------------------------------------------------

    /**
     * Ventas cuyo pago espera la revisión del comercio, y las rechazadas en
     * los últimos 14 días: en ese caso los cartones se anularon y el monto se
     * le reembolsó al vendedor, que debe devolvérselo al comprador.
     *
     * @return array{pending_count: int, pending_cards: int, oldest_pending_at: ?string, rejected: array<int, array<string, mixed>>}
     */
    private function buildPaymentReview(string $userId): array
    {
        $pending = \App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment::query()
            ->where('bingo_card_payments.seller_user_id', $userId)
            ->where('bingo_card_payments.payment_status', 'PENDING')
            ->join('bingo_card_lots as l', 'l.id', '=', 'bingo_card_payments.card_lot_id')
            ->selectRaw('COUNT(*) as payments, COALESCE(SUM(l.card_count), 0) as cards, MIN(bingo_card_payments.created_at) as oldest')
            ->toBase()
            ->first();

        $rejected = \App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment::query()
            ->where('seller_user_id', $userId)
            ->where('payment_status', 'REJECTED')
            ->where('processed_at', '>=', now()->subDays(14))
            ->with(['externalPlayer:id,first_name,last_name,phone', 'cardLot:id,card_count', 'currency:id,symbol', 'room:id,uuid_code'])
            ->latest('processed_at')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id'          => (string) $p->id,
                'buyer'       => $p->externalPlayer
                    ? trim("{$p->externalPlayer->first_name} {$p->externalPlayer->last_name}")
                    : '—',
                'phone'       => $p->externalPlayer?->phone,
                'cards'       => (int) ($p->cardLot?->card_count ?? 0),
                'amount'      => trim(($p->currency?->symbol ?? '') . ' ' . number_format((float) $p->payment_amount, 2, ',', '.')),
                'room'        => $p->room?->uuid_code,
                'rejected_at' => $p->processed_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return [
            'pending_count'     => (int) ($pending->payments ?? 0),
            'pending_cards'     => (int) ($pending->cards ?? 0),
            'oldest_pending_at' => $pending?->oldest ? Carbon::parse($pending->oldest)->toIso8601String() : null,
            'rejected'          => $rejected,
        ];
    }

    // -------------------------------------------------------------------------
    // Salas por cerrar venta (próximas 24 h)
    // -------------------------------------------------------------------------

    /**
     * Salas que el vendedor todavía puede vender y cuya venta cierra en las
     * próximas 24 h — mismo listado que el punto de venta
     * (BingoCardGenerateService::listSellableRooms), para empujar la venta
     * antes del cierre. Best-effort: sin comercio resoluble, lista vacía.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildClosingRooms(?Seller $seller): array
    {
        if (!$seller || !$seller->hasActiveHierarchy()) {
            return [];
        }

        try {
            $service  = app(\App\Domains\Games\Bingo\Services\Card\Generate\BingoCardGenerateService::class);
            $now      = now();
            $deadline = $now->copy()->addDay();

            return collect([$now->toDateString(), $deadline->toDateString()])
                ->unique()
                ->flatMap(fn (string $date) => $service->listSellableRooms($date))
                ->filter(fn (array $room) => ($room['sales_open'] ?? false)
                    && !empty($room['sales_close_at'])
                    && Carbon::parse($room['sales_close_at'])->between($now, $deadline))
                ->unique('id')
                ->sortBy('sales_close_at')
                ->take(4)
                ->map(fn (array $room) => [
                    'id'             => $room['id'],
                    'uuid_code'      => $room['uuid_code'],
                    'time'           => $room['time'] ?? null,
                    'sales_close_at' => $room['sales_close_at'],
                    'total_players'  => (int) ($room['total_players'] ?? 0),
                    'is_own'         => (bool) ($room['is_own'] ?? false),
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Dashboard vendedor: no se pudieron listar salas por cerrar', ['error' => $e->getMessage()]);

            return [];
        }
    }

    // -------------------------------------------------------------------------
    // Códigos de compra (compradores recurrentes)
    // -------------------------------------------------------------------------

    /** @return array{active: int, remaining_uses: int, expiring_soon: int} */
    private function buildPurchaseCodes(string $userId): array
    {
        $active = \App\Models\Roles\Player\External\ExternalBuyerPurchaseCode::query()
            ->where('seller_user_id', $userId)
            ->usable()
            ->get(['id', 'max_uses', 'uses_count', 'expires_at']);

        return [
            'active'         => $active->count(),
            'remaining_uses' => (int) $active->sum(fn ($c) => $c->remainingUses()),
            'expiring_soon'  => $active->filter(fn ($c) => $c->expires_at?->lte(now()->addDays(3)))->count(),
        ];
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

        // Solo salas que todavía venden o juegan: las terminadas/canceladas ya
        // no aportan nada al vendedor y llenaban la lista.
        return SellerRoomLimits::where('seller_id', $seller->id)
            ->whereHas('room.status', fn ($q) => $q->whereIn('code', ['ACTIVE', 'IN_PROGRESS']))
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

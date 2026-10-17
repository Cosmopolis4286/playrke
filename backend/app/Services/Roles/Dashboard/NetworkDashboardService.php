<?php

namespace App\Services\Roles\Dashboard;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Services\Card\Generate\BingoCardGenerateService;
use App\Models\Currency;
use App\Models\Roles\Seller;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bloques de dashboard compartidos por Seller, Subsidiary y Commerce, para
 * que los tres midan las ventas igual:
 *
 *  - Nunca se suman monedas distintas: o se normaliza a USD con la tasa de
 *    cada lote (sale_amount / exchange_rate, mismo criterio que
 *    SellerRankingService) o se muestra la moneda principal + "N más".
 *  - Los lotes REFUNDED/INACTIVE (pago rechazado, sala cancelada) no son venta.
 */
final class NetworkDashboardService
{
    /** Días sin vender a partir de los cuales un vendedor se marca "inactivo". */
    public const IDLE_DAYS = 7;

    public function __construct(
        private readonly BingoCardGenerateService $cardGenerate,
    ) {}

    /**
     * Cantidad de filas y monto de la moneda principal (la de mayor monto),
     * con "+N moneda(s)" si hay más.
     *
     * @return array{count: int, amount: ?string}
     */
    public function currencySummary(Builder $query, string $column): array
    {
        $table = $query->getModel()->getTable();

        $rows = (clone $query)
            ->groupBy("{$table}.currency_id")
            ->selectRaw("{$table}.currency_id AS currency_id, COUNT(*) AS total, COALESCE(SUM({$table}.{$column}), 0) AS amount")
            ->toBase()
            ->get()
            ->sortByDesc(fn ($r) => (float) $r->amount)
            ->values();

        $count = (int) $rows->sum('total');

        if ($count === 0) {
            return ['count' => 0, 'amount' => null];
        }

        $primary = $rows->first();
        $amount  = $this->formatAmount($primary->currency_id, (float) $primary->amount);

        if ($rows->count() > 1) {
            $amount .= ' +' . ($rows->count() - 1) . ' moneda(s)';
        }

        return ['count' => $count, 'amount' => $amount];
    }

    public function formatAmount(int|string|null $currencyId, float $amount): string
    {
        $currency = $currencyId ? Currency::find($currencyId) : null;

        return $currency
            ? trim(($currency->symbol ?? '') . ' ' . $currency->formatAmount($amount))
            : number_format($amount, 2, ',', '.');
    }

    /** Lotes que cuentan como venta (excluye reembolsados/anulados). */
    public function soldLots(): Builder
    {
        return BingoCardLot::query()->where('bingo_card_lots.status', 'ACTIVE');
    }

    /** Suma de ventas en USD a la tasa de cada lote. */
    public function salesUsd(Builder $lots): float
    {
        return round((float) (clone $lots)
            ->selectRaw('COALESCE(SUM(bingo_card_lots.sale_amount / NULLIF(bingo_card_lots.exchange_rate, 0)), 0) AS usd')
            ->toBase()
            ->value('usd'), 2);
    }

    /**
     * Top 10 de vendedores del período, en USD (DashboardSellerSalesList ya
     * los muestra con "$": antes mezclaba bolívares, pesos y dólares).
     *
     * @return array<int, array<string, mixed>>
     */
    public function salesBySellerUsd(Builder $lots, string $fallbackName = 'Vendedor'): array
    {
        return (clone $lots)
            ->select([
                'bingo_card_lots.seller_user_id',
                DB::raw('SUM(bingo_card_lots.sale_amount / NULLIF(bingo_card_lots.exchange_rate, 0)) AS total_sales'),
                DB::raw('SUM(bingo_card_lots.card_count) AS total_cards'),
                // commission_amount = comisión del VENDEDOR persistida por lote.
                DB::raw('SUM(bingo_card_lots.commission_amount / NULLIF(bingo_card_lots.exchange_rate, 0)) AS total_commission'),
            ])
            ->groupBy('bingo_card_lots.seller_user_id')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->with('seller:id,first_name,last_name,username')
            ->get()
            ->map(fn (BingoCardLot $row) => [
                'seller_user_id'   => $row->seller_user_id ? (string) $row->seller_user_id : null,
                'seller_name'      => $row->seller
                    ? (trim("{$row->seller->first_name} {$row->seller->last_name}") ?: $row->seller->username)
                    : $fallbackName,
                'total_sales'      => round((float) $row->total_sales, 2),
                'total_cards'      => (int) $row->total_cards,
                'total_commission' => round((float) $row->total_commission, 2),
            ])
            ->values()
            ->all();
    }

    /**
     * Salud de la red de vendedores: cuántos vendieron hoy, cuántos llevan
     * IDLE_DAYS días o más sin vender (con los 5 de mayor inactividad, para
     * contactarlos) y cuántos no están activos.
     *
     * @param Collection<int, Seller> $sellers
     * @return array{total: int, active: int, sold_today: int, inactive: int, idle_count: int, idle: array<int, array<string, mixed>>}
     */
    public function networkHealth(Collection $sellers, ?Carbon $now = null): array
    {
        $now = $now ?? now();

        if ($sellers->isEmpty()) {
            return ['total' => 0, 'active' => 0, 'sold_today' => 0, 'inactive' => 0, 'idle_count' => 0, 'idle' => []];
        }

        $lastSale = $this->soldLots()
            ->whereIn('seller_user_id', $sellers->pluck('user_id')->all())
            ->groupBy('seller_user_id')
            ->selectRaw('seller_user_id, MAX(created_at) AS last_sale_at')
            ->toBase()
            ->pluck('last_sale_at', 'seller_user_id')
            ->mapWithKeys(fn ($at, $id) => [(string) $id => Carbon::parse($at)]);

        $active    = $sellers->where('status', 'ACTIVE');
        $startDay  = $now->copy()->startOfDay();
        $idleSince = $now->copy()->subDays(self::IDLE_DAYS);

        // Solo los ACTIVOS cuentan como inactivos-por-no-vender: un vendedor
        // bloqueado ya se cuenta aparte y no hay nada que reactivar.
        $idle = $active
            ->filter(fn (Seller $s) => !($lastSale[(string) $s->user_id] ?? null)?->gte($idleSince))
            ->map(fn (Seller $s) => [
                'name'         => $s->name,
                'phone'        => $s->phone,
                'last_sale_at' => ($lastSale[(string) $s->user_id] ?? null)?->toIso8601String(),
                'days'         => ($at = $lastSale[(string) $s->user_id] ?? null) ? (int) $at->diffInDays($now) : null,
            ])
            // Nunca vendió → primero; luego los que llevan más días.
            ->sortByDesc(fn (array $r) => $r['days'] ?? PHP_INT_MAX)
            ->values();

        return [
            'total'      => $sellers->count(),
            'active'     => $active->count(),
            'sold_today' => $sellers->filter(fn (Seller $s) => ($lastSale[(string) $s->user_id] ?? null)?->gte($startDay))->count(),
            'inactive'   => $sellers->count() - $active->count(),
            'idle_count' => $idle->count(),
            'idle'       => $idle->take(5)->all(),
        ];
    }

    /**
     * Pagos de la red en revisión y rechazados en los últimos 7 días.
     *
     * @param string[] $sellerUserIds
     * @return array{pending_count: int, pending_amount: ?string, oldest_pending_at: ?string, rejected_count: int}
     */
    public function networkPayments(array $sellerUserIds, ?Carbon $now = null): array
    {
        $now     = $now ?? now();
        $payments = fn () => BingoCardPayment::query()->whereIn('bingo_card_payments.seller_user_id', $sellerUserIds);

        $pending = $this->currencySummary($payments()->where('bingo_card_payments.payment_status', 'PENDING'), 'payment_amount');
        $oldest  = $pending['count'] > 0
            ? $payments()->where('payment_status', 'PENDING')->min('created_at')
            : null;

        return [
            'pending_count'     => $pending['count'],
            'pending_amount'    => $pending['amount'],
            'oldest_pending_at' => $oldest ? Carbon::parse($oldest)->toIso8601String() : null,
            'rejected_count'    => $payments()
                ->where('payment_status', 'REJECTED')
                ->where('processed_at', '>=', $now->copy()->subDays(7))
                ->count(),
        ];
    }

    /**
     * Premios de cartones vendidos por la red aún sin entregar (se acreditan
     * al comercio — BingoCardPrize::getUserId() — y el comprador externo no
     * tiene billetera: alguien de la red tiene que entregárselo).
     *
     * @param string[] $sellerUserIds
     * @return array{count: int, amount: ?string}
     */
    public function prizesToDeliver(array $sellerUserIds): array
    {
        return $this->currencySummary(
            BingoCardPrize::query()
                ->whereIn('bingo_card_prizes.seller_user_id', $sellerUserIds)
                ->whereIn('bingo_card_prizes.prize_status', ['waiting', 'processing']),
            'local_amount',
        );
    }

    /**
     * Próximas 5 salas propias de hoy y mañana del usuario autenticado
     * (comercio o cliente API), con su ocupación. Reusa el listado del punto
     * de venta (listSellableRooms), filtrado a las propias.
     *
     * @return array<int, array<string, mixed>>
     */
    public function upcomingOwnRooms(Carbon $now): array
    {
        try {
            return collect([$now->toDateString(), $now->copy()->addDay()->toDateString()])
                ->flatMap(fn (string $date) => $this->cardGenerate->listSellableRooms($date))
                ->filter(fn (array $room) => $room['is_own'] ?? false)
                ->unique('id')
                // Inicio manual (sin hora) al final.
                ->sortBy(fn (array $room) => $room['starts_at'] ?? '9999')
                ->take(5)
                ->map(fn (array $room) => [
                    'id'             => $room['id'],
                    'uuid_code'      => $room['uuid_code'],
                    'starts_at'      => $room['starts_at'] ?? null,
                    'is_manual'      => (bool) ($room['is_manual'] ?? false),
                    'sales_open'     => (bool) ($room['sales_open'] ?? false),
                    'total_players'  => (int) ($room['total_players'] ?? 0),
                    'max_players'    => $room['max_players'] ?? null,
                    'total_cards'    => (int) ($room['total_cards'] ?? 0),
                    'card_price_usd' => (float) ($room['card_price_usd'] ?? 0),
                    'prize_pool_usd' => (float) ($room['prize_pool'] ?? 0),
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Dashboard: no se pudieron listar las próximas salas propias', ['error' => $e->getMessage()]);

            return [];
        }
    }
}

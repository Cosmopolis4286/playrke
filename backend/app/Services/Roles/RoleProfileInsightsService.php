<?php

namespace App\Services\Roles;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Models\Currency;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Indicadores de las fichas de autogestión (Commerce/Subsidiary/Seller
 * "Mi …"): desempeño de ventas, tendencia, seguridad de la cuenta y alertas.
 *
 * Genérico por rol: cada *ProfileService le pasa
 *  - un scope sobre bingo_card_lots (qué ventas son "suyas"), y
 *  - la columna de comisión que le corresponde en el reparto en cascada
 *    (CommissionCalculationService::networkSplit()): commission_amount
 *    (vendedor), subsidiary_commission_amount (agencia) o
 *    commerce_commission_amount (comercializadora).
 *
 * Montos en moneda LOCAL de cada lote: nunca se suman monedas distintas. Los
 * KPIs monetarios se dan en la moneda principal (la de más ventas del mes, o
 * del mes anterior si este aún no tiene); el resto va en other_currencies.
 * Conteos (cartones, ventas) sí suman todas las monedas.
 *
 * Solo lotes ACTIVE: un lote REFUNDED (sala cancelada, comprobante
 * rechazado) no es una venta.
 */
final class RoleProfileInsightsService
{
    private const TREND_DAYS = 30;

    /**
     * @param callable(Builder): void $lotScope
     */
    public function performance(callable $lotScope, string $commissionColumn): array
    {
        $now        = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        // Mismo tramo del mes anterior (1..hoy), no el mes completo: comparar
        // 3 días de octubre contra 30 de septiembre siempre daría "-90%".
        $prevStart  = $monthStart->copy()->subMonthNoOverflow();
        $prevEnd    = $now->copy()->subMonthNoOverflow();

        $current  = $this->aggregateByCurrency($lotScope, $commissionColumn, $monthStart, $now);
        $previous = $this->aggregateByCurrency($lotScope, $commissionColumn, $prevStart, $prevEnd);

        $primaryId = $current->sortByDesc('sales')->keys()->first()
            ?? $previous->sortByDesc('sales')->keys()->first();

        $currencyIds = $current->keys()->merge($previous->keys())->unique()->values();
        $currencies  = Currency::whereIn('id', $currencyIds)->get(['id', 'iso_code', 'symbol'])->keyBy(fn ($c) => (string) $c->id);

        $cur  = $primaryId ? $current->get($primaryId) : null;
        $prev = $primaryId ? $previous->get($primaryId) : null;

        $lifetime = $this->lifetime($lotScope, $commissionColumn, $primaryId);

        return [
            'period_label'     => ucfirst($now->translatedFormat('F Y')),
            'currency'         => $primaryId && $currencies->has($primaryId)
                ? ['iso' => $currencies[$primaryId]->iso_code, 'symbol' => $currencies[$primaryId]->symbol]
                : null,
            'kpis' => [
                'sales'      => $this->kpi((float) ($cur['sales'] ?? 0), (float) ($prev['sales'] ?? 0)),
                'commission' => $this->kpi((float) ($cur['commission'] ?? 0), (float) ($prev['commission'] ?? 0)),
                'cards'      => $this->kpi((float) $current->sum('cards'), (float) $previous->sum('cards')),
                'lots'       => $this->kpi((float) $current->sum('lots'), (float) $previous->sum('lots')),
            ],
            'other_currencies' => $current->except($primaryId ? [$primaryId] : [])
                ->map(fn (array $row, string $id) => [
                    'iso'        => $currencies[$id]->iso_code ?? '—',
                    'symbol'     => $currencies[$id]->symbol ?? '',
                    'sales'      => round((float) $row['sales'], 2),
                    'commission' => round((float) $row['commission'], 2),
                ])
                ->values()
                ->all(),
            'trend'    => $this->trend($lotScope),
            'lifetime' => $lifetime,
        ];
    }

    /**
     * Estado de seguridad de la cuenta dueña de la ficha — la gestión real
     * vive en /profile (pestaña Seguridad); aquí solo se señala qué falta.
     *
     * @return array{email_verified: bool, two_factor_enabled: bool, withdrawal_pin_set: bool, login_streak_days: int, score: int}
     */
    public function security(string $userId): array
    {
        $user = User::query()
            ->select(['id', 'email_verified_at', 'is_two_factor_enabled', 'withdrawal_pin_hash', 'login_streak_days'])
            ->findOrFail($userId);

        $checks = [
            'email_verified'     => $user->email_verified_at !== null,
            'two_factor_enabled' => (bool) $user->is_two_factor_enabled,
            'withdrawal_pin_set' => !empty($user->withdrawal_pin_hash),
        ];

        return $checks + [
            'login_streak_days' => (int) $user->login_streak_days,
            'score'             => (int) round(count(array_filter($checks)) / count($checks) * 100),
        ];
    }

    /**
     * Cartones vendidos este mes por vendedor — para la columna "este mes"
     * de las listas de red (agencias/vendedores) de Commerce y Subsidiary.
     *
     * @param string[] $sellerUserIds
     * @return array<string, int> seller_user_id => cartones
     */
    public function monthCardsBySeller(array $sellerUserIds): array
    {
        if ($sellerUserIds === []) {
            return [];
        }

        return BingoCardLot::query()
            ->active()
            ->whereIn('seller_user_id', $sellerUserIds)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->groupBy('seller_user_id')
            ->pluck(DB::raw('SUM(card_count) as cards'), 'seller_user_id')
            ->mapWithKeys(fn ($cards, $id) => [(string) $id => (int) $cards])
            ->all();
    }

    /**
     * @return array{level: 'danger'|'warning'|'info', icon: string, title: string, message: string, action: array{label: string, href: string}|null}
     */
    public static function alert(string $level, string $icon, string $title, string $message, ?string $actionLabel = null, ?string $actionHref = null): array
    {
        return [
            'level'   => $level,
            'icon'    => $icon,
            'title'   => $title,
            'message' => $message,
            'action'  => $actionLabel && $actionHref ? ['label' => $actionLabel, 'href' => $actionHref] : null,
        ];
    }

    // -------------------------------------------------------------------------

    /**
     * @param callable(Builder): void $lotScope
     * @return Collection<string, array{sales: float, commission: float, cards: int, lots: int}>
     */
    private function aggregateByCurrency(callable $lotScope, string $commissionColumn, Carbon $from, Carbon $to): Collection
    {
        return BingoCardLot::query()
            ->active()
            ->where($lotScope)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('currency_id')
            ->get([
                'currency_id',
                DB::raw('SUM(sale_amount) as sales_sum'),
                DB::raw("SUM(COALESCE({$commissionColumn}, 0)) as commission_sum"),
                DB::raw('SUM(card_count) as cards_sum'),
                DB::raw('COUNT(*) as lots_count'),
            ])
            ->mapWithKeys(fn ($row) => [(string) $row->currency_id => [
                'sales'      => (float) $row->sales_sum,
                'commission' => (float) $row->commission_sum,
                'cards'      => (int) $row->cards_sum,
                'lots'       => (int) $row->lots_count,
            ]]);
    }

    /**
     * @param callable(Builder): void $lotScope
     * @return list<array{date: string, count: int}>
     */
    private function trend(callable $lotScope): array
    {
        $start = Carbon::now()->subDays(self::TREND_DAYS - 1)->startOfDay();

        $byDay = BingoCardLot::query()
            ->active()
            ->where($lotScope)
            ->where('created_at', '>=', $start)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck(DB::raw('SUM(card_count) as cards'), DB::raw('DATE(created_at) as day'));

        $trend = [];
        for ($i = 0; $i < self::TREND_DAYS; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $trend[] = ['date' => $date, 'count' => (int) ($byDay[$date] ?? 0)];
        }

        return $trend;
    }

    /**
     * @param callable(Builder): void $lotScope
     * @return array{cards: int, lots: int, commission: float, first_sale_at: string|null}
     */
    private function lifetime(callable $lotScope, string $commissionColumn, ?string $primaryCurrencyId): array
    {
        $totals = BingoCardLot::query()
            ->active()
            ->where($lotScope)
            ->selectRaw('COALESCE(SUM(card_count), 0) as cards_sum, COUNT(*) as lots_count, MIN(created_at) as first_sale')
            ->first();

        $commission = $primaryCurrencyId
            ? (float) BingoCardLot::query()->active()->where($lotScope)->where('currency_id', $primaryCurrencyId)->sum($commissionColumn)
            : 0.0;

        return [
            'cards'         => (int) ($totals->cards_sum ?? 0),
            'lots'          => (int) ($totals->lots_count ?? 0),
            'commission'    => round($commission, 2),
            'first_sale_at' => $totals?->first_sale ? Carbon::parse($totals->first_sale)->toIso8601String() : null,
        ];
    }

    /** @return array{value: float, previous: float, change_pct: int|null} */
    private function kpi(float $value, float $previous): array
    {
        return [
            'value'      => round($value, 2),
            'previous'   => round($previous, 2),
            // null = sin base de comparación (mes anterior en 0): la UI muestra
            // "nuevo" en vez de un "+100%" engañoso.
            'change_pct' => $previous > 0 ? (int) round(($value - $previous) / $previous * 100) : null,
        ];
    }
}

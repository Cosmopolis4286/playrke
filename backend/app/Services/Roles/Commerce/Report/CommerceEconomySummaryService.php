<?php

declare(strict_types=1);

namespace App\Services\Roles\Commerce\Report;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Creator\BingoCreatorSale;
use App\Models\Currency;
use App\Models\Roles\Commerce;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reporte financiero consolidado propio de una comercializadora — análogo
 * scoped a EconomySummaryService (SuperAdmin), pero limitado a Bingo: Trivia
 * no tiene cadena de comercializadores (se paga directo de la wallet del
 * jugador, ver EconomySummaryService::byCommerce()), así que no hay nada de
 * Trivia que consolidar por comercio.
 *
 * A diferencia de CommerceDashboardContextService::buildCommissionThisMonth()
 * (mes en curso, para el widget del dashboard), este servicio acepta CUALQUIER
 * rango de fechas — es la base del reporte con histórico real.
 *
 * Antes de este servicio, un commerce con vendedores/agencias activas no
 * tenía forma de ver "cuánto vendió y cuánto generó de comisión TODA mi red"
 * en un rango arbitrario — el dashboard solo cubre el mes en curso, y
 * bingo.sales-report (BingoReportController) solo cuenta lo que el propio
 * commerce vendió con su cuenta, no su red completa (ver Commerce::networkUserIds()).
 */
final class CommerceEconomySummaryService
{
    /**
     * @param  string      $commerceUserId  user_id del Commerce autenticado.
     * @param  string|null $startDate       'Y-m-d', null = sin límite inferior.
     * @param  string|null $endDate         'Y-m-d', null = sin límite superior (hoy).
     */
    public function getSummary(string $commerceUserId, ?string $startDate = null, ?string $endDate = null): array
    {
        $commerce = Commerce::where('user_id', $commerceUserId)->first(['id', 'user_id', 'commission']);
        $networkUserIds = $commerce ? $commerce->networkUserIds() : [$commerceUserId];

        return [
            'by_currency'   => $this->byCurrency($networkUserIds, $startDate, $endDate),
            'by_seller'     => $this->bySeller($networkUserIds, $startDate, $endDate),
            'totals'        => $this->totals($networkUserIds, $startDate, $endDate),
        ];
    }

    /**
     * Ventas + comisión real (rke_commission + room_commission +
     * seller_commission, ya desglosadas por BingoCreatorSale — no una
     * re-derivación de tasas) agrupadas por moneda, dentro del rango.
     */
    private function byCurrency(array $networkUserIds, ?string $startDate, ?string $endDate): array
    {
        $query = BingoCreatorSale::query()
            ->whereIn('creator_user_id', $networkUserIds)
            ->select([
                'currency_id',
                DB::raw('SUM(sale_amount) as income'),
                DB::raw('SUM(rke_commission + room_commission + COALESCE(seller_commission, 0)) as commission'),
                DB::raw('SUM(prize_pool_amount) as prize_pool'),
            ])
            ->groupBy('currency_id');

        $this->applyDateRange($query, $startDate, $endDate);

        $rows = $query->get();

        $currencies = Currency::whereIn('id', $rows->pluck('currency_id'))
            ->get(['id', 'iso_code', 'symbol'])
            ->keyBy('id');

        return $rows->map(function ($row) use ($currencies) {
            $currency = $currencies->get($row->currency_id);

            return [
                'currency_id' => (string) $row->currency_id,
                'iso_code'    => $currency?->iso_code ?? 'N/D',
                'symbol'      => $currency?->symbol ?? '',
                'income'      => round((float) $row->income, 2),
                'commission'  => round((float) $row->commission, 2),
                'prize_pool'  => round((float) $row->prize_pool, 2),
            ];
        })->sortByDesc('income')->values()->all();
    }

    /**
     * Ventas + comisión por vendedor de la red (mismo criterio que
     * CommerceDashboardContextService::buildSalesBySeller(), pero sin límite
     * de 10 filas ni acotado al mes en curso — este es el reporte completo).
     */
    private function bySeller(array $networkUserIds, ?string $startDate, ?string $endDate): array
    {
        $query = BingoCardLot::query()
            ->whereIn('user_id', $networkUserIds)
            ->select([
                'seller_user_id',
                DB::raw('SUM(sale_amount) as total_sales'),
                DB::raw('SUM(card_count) as total_cards'),
                DB::raw('SUM(commission_amount) as total_commission'),
            ])
            ->groupBy('seller_user_id')
            ->with('seller:id,first_name,last_name,username');

        $this->applyDateRange($query, $startDate, $endDate);

        return $query->orderByDesc(DB::raw('SUM(sale_amount)'))
            ->get()
            ->map(function (BingoCardLot $row) {
                $sellerName = $row->seller
                    ? trim("{$row->seller->first_name} {$row->seller->last_name}") ?: $row->seller->username
                    : 'Venta directa';

                return [
                    'seller_user_id'   => $row->seller_user_id ? (string) $row->seller_user_id : null,
                    'seller_name'      => $sellerName,
                    'total_sales'      => round((float) $row->total_sales, 2),
                    'total_cards'      => (int) $row->total_cards,
                    'total_commission' => round((float) $row->total_commission, 2),
                ];
            })
            ->values()
            ->all();
    }

    private function totals(array $networkUserIds, ?string $startDate, ?string $endDate): array
    {
        $query = BingoCreatorSale::query()->whereIn('creator_user_id', $networkUserIds);
        $this->applyDateRange($query, $startDate, $endDate);

        $totals = $query->selectRaw('
            SUM(sale_amount) as total_income,
            SUM(rke_commission + room_commission + COALESCE(seller_commission, 0)) as total_commission,
            SUM(prize_pool_amount) as total_prize_pool,
            COUNT(*) as total_room_currency_rows
        ')->first();

        return [
            'total_income'     => round((float) ($totals->total_income ?? 0), 2),
            'total_commission' => round((float) ($totals->total_commission ?? 0), 2),
            'total_prize_pool' => round((float) ($totals->total_prize_pool ?? 0), 2),
        ];
    }

    private function applyDateRange($query, ?string $startDate, ?string $endDate): void
    {
        if ($startDate) {
            $query->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }
    }
}

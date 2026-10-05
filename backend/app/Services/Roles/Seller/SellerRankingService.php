<?php

declare(strict_types=1);

namespace App\Services\Roles\Seller;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Models\Roles\Seller;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Ranking de vendedores por volumen del mes — ver
 * progreso/estrategia-producto-plan.md, Fase 7. Hoy `Seller.commission` es
 * un decimal fijo, sin ranking ni meta; no existía ningún mecanismo de
 * visibilidad de desempeño entre vendedores.
 *
 * Deliberadamente mínimo: solo ranking + meta simple (umbral fijo
 * configurable por env, no por vendedor). Comisión progresiva (Fase 10) NO
 * se construye aquí — depende de calibrar con datos reales de este ranking
 * primero, no al revés.
 *
 * Fuente: BingoCardLot agrupado por seller_user_id — solo vendedores reales,
 * sin lotes REFUNDED, y `commission_amount` expresado en USD (comisión de
 * cada lote ÷ su tasa) para que el ranking y la meta comparen lo mismo sin
 * importar la moneda en que vende cada uno.
 */
final class SellerRankingService
{
    private const CACHE_KEY = 'seller-ranking:monthly';
    private const CACHE_TTL = 90; // mismo TTL que el resto de agregados de dashboard

    /**
     * Meta mensual mínima de comisión para considerarse "en meta" —
     * deliberadamente un umbral fijo global, no configurable por vendedor
     * todavía (eso sería sobre-arquitectura sin datos que lo justifiquen).
     */
    private const MONTHLY_GOAL_COMMISSION = 50.0;

    public function __construct(
        private readonly SellerCommissionTierService $commissionTierService = new SellerCommissionTierService(),
    ) {}

    public function getMonthlyRanking(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->buildRanking());
    }

    /**
     * Posición y stats de un vendedor específico dentro del ranking mensual
     * — para que el propio vendedor consulte "cómo voy" sin exponerle la
     * lista completa de todos los demás.
     */
    public function getSellerStanding(string $userId): ?array
    {
        $ranking = $this->getMonthlyRanking();

        $row = collect($ranking['rows'])->firstWhere('seller_user_id', $userId);

        if (! $row) {
            return null;
        }

        $tierData = [];
        if ($this->commissionTierService->isEnabled()) {
            $tierData = ['commission_tier' => $this->commissionTierService->tierProgress((float) $row['sale_amount'])];
        }

        return array_merge($row, $tierData, [
            'total_sellers' => $ranking['total_sellers'],
            'goal'          => self::MONTHLY_GOAL_COMMISSION,
        ]);
    }

    private function buildRanking(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        // Solo vendedores reales (antes entraban jugadores que compraron con
        // su billetera: su seller_user_id es el propio jugador e inflaban
        // "de N vendedores"), sin lotes reembolsados, y la comisión llevada a
        // USD con la tasa de cada lote — antes se sumaban bolívares, pesos y
        // dólares como un mismo número y se comparaban contra una meta en "$".
        $byUser = BingoCardLot::where('created_at', '>=', $startOfMonth)
            ->whereIn('seller_user_id', Seller::query()->select('user_id'))
            ->where('status', '!=', 'REFUNDED')
            ->select('seller_user_id')
            ->selectRaw('SUM(card_count) AS cards_sold')
            ->selectRaw('SUM(sale_amount) AS sale_amount')
            ->selectRaw('SUM(commission_amount / NULLIF(exchange_rate, 0)) AS commission_usd')
            ->groupBy('seller_user_id')
            ->get();

        if ($byUser->isEmpty()) {
            return ['rows' => [], 'total_sellers' => 0, 'goal' => self::MONTHLY_GOAL_COMMISSION];
        }

        $sellers = Seller::whereIn('user_id', $byUser->pluck('seller_user_id'))
            ->get(['id', 'user_id', 'name'])
            ->keyBy('user_id');

        $rows = $byUser
            ->map(function ($row) use ($sellers) {
                $seller = $sellers->get($row->seller_user_id);
                // En USD: comparable entre vendedores de distintas monedas.
                $commissionAmount = (float) $row->commission_usd;

                return [
                    'seller_user_id'     => (string) $row->seller_user_id,
                    'seller_name'        => $seller?->name ?? 'Desconocido',
                    'cards_sold'         => (int) $row->cards_sold,
                    'sale_amount'        => round((float) $row->sale_amount, 2),
                    'commission_amount'  => round($commissionAmount, 2),
                    'goal_reached'       => $commissionAmount >= self::MONTHLY_GOAL_COMMISSION,
                ];
            })
            ->sortByDesc('commission_amount')
            ->values();

        $rows = $rows->map(function (array $row, int $index) {
            $row['rank'] = $index + 1;
            return $row;
        });

        return [
            'rows'          => $rows->all(),
            'total_sellers' => $rows->count(),
            'goal'          => self::MONTHLY_GOAL_COMMISSION,
        ];
    }
}

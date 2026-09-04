<?php

namespace App\Services\Roles\SuperAdmin\Dashboard;

use App\Domains\Games\Bingo\Models\Creator\BingoCreatorSale;
use App\Models\Currency;
use App\Models\Roles\Seller;
use App\Models\User;
use App\Domains\Wallet\Models\WalletMovement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Resumen económico consolidado cross-juego para SuperAdmin — hasta ahora
 * RkeSummaryService y TriviaSummaryService reportaban aislados por juego, sin
 * ninguna vista que cruce "cuánto entró, cuánto se pagó, cuánto quedó de
 * comisión" en un solo lugar (ver progreso/estrategia-producto-plan.md, Fase 2).
 *
 * No reemplaza ninguno de los dos servicios existentes ni introduce un ledger
 * nuevo — agrega sobre datos ya existentes:
 *  - Bingo: bingo_creator_sales (ya desglosa sale_amount/comisiones por moneda).
 *  - Trivia: wallet_movements TYPE_TRIVIA_ENTRY/TYPE_PRIZE/TYPE_BONUS_PRIZE
 *    filtrado por metadata.game_id — se usa wallet_movements (MySQL) en vez de
 *    trivia_rooms (Postgres) porque el dinero real siempre pasa por la wallet
 *    del jugador, y cruzar conexiones distintas en una sola query no es viable
 *    (ver la regla de arquitectura documentada en WalletService).
 *  - Desglose por Comercializadora: solo Bingo lo tiene hoy (jerarquía
 *    comercial de vendedores es exclusiva de Bingo, ver
 *    ALCANCE_JUEGOS_Y_COMERCIO.md sección 3.3) — Trivia se paga directo de la
 *    wallet del jugador, sin cadena de vendedores.
 */
final class EconomySummaryService
{
    private const CACHE_KEY = 'superadmin:economy-summary';
    private const CACHE_TTL = 90; // mismo TTL que el resto de agregados del dashboard superadmin

    public function getSummary(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->buildSummary());
    }

    private function buildSummary(): array
    {
        return [
            'by_game'       => $this->byGame(),
            'by_currency'   => $this->byCurrency(),
            'by_commerce'   => $this->byCommerce(),
            'totals'        => $this->totals(),
        ];
    }

    /**
     * Dinero ingresado (ventas Bingo + entradas Trivia) y premios pagados,
     * por juego. Bingo desde bingo_creator_sales (sale_amount ya es lo que
     * pagó el comprador); Trivia desde wallet_movements TYPE_TRIVIA_ENTRY.
     */
    private function byGame(): array
    {
        $bingoIncome = (float) BingoCreatorSale::sum('sale_amount');

        // metadata.game_id en wallet_movements guarda el ID de la sala/juego,
        // no un slug de juego — no hay forma de filtrar premios por "bingo"
        // vs "trivia" con una condición sobre esa columna. Se reporta el
        // total de premios pagados sin desglose por juego (ver totals()),
        // en vez de fingir una precisión que los datos no tienen.
        $triviaIncome = (float) WalletMovement::query()
            ->where('type', WalletMovement::TYPE_TRIVIA_ENTRY)
            ->where('status', WalletMovement::STATUS_COMPLETED)
            ->sum('amount');

        return [
            [
                'game'          => 'bingo',
                'game_label'    => 'Bingo',
                'income'        => round($bingoIncome, 2),
                'commission'    => round((float) BingoCreatorSale::sum(DB::raw('rke_commission + room_commission + seller_commission')), 2),
            ],
            [
                'game'          => 'trivia',
                'game_label'    => 'Trivia',
                'income'        => round($triviaIncome, 2),
                'commission'    => null, // no desglosable desde wallet_movements — ver TriviaSummaryService para comisión estimada por sala
            ],
        ];
    }

    /**
     * Dinero ingresado por moneda, cruzando Bingo (bingo_creator_sales) y
     * Trivia (wallet_movements TYPE_TRIVIA_ENTRY) — la vista que hoy no existe
     * en ningún lugar del panel.
     */
    private function byCurrency(): array
    {
        $bingoByCurrency = BingoCreatorSale::query()
            ->select('currency_id', DB::raw('SUM(sale_amount) AS income'))
            ->groupBy('currency_id')
            ->pluck('income', 'currency_id');

        $triviaByCurrency = WalletMovement::query()
            ->join('wallets', 'wallets.id', '=', 'wallet_movements.wallet_id')
            ->where('wallet_movements.type', WalletMovement::TYPE_TRIVIA_ENTRY)
            ->where('wallet_movements.status', WalletMovement::STATUS_COMPLETED)
            ->select('wallets.currency_id', DB::raw('SUM(wallet_movements.amount) AS income'))
            ->groupBy('wallets.currency_id')
            ->pluck('income', 'currency_id');

        $currencyIds = collect($bingoByCurrency->keys())->merge($triviaByCurrency->keys())->unique();
        $currencies  = Currency::whereIn('id', $currencyIds)->get(['id', 'iso_code', 'symbol'])->keyBy('id');

        return $currencyIds->map(function ($currencyId) use ($currencies, $bingoByCurrency, $triviaByCurrency) {
            $currency = $currencies->get($currencyId);

            return [
                'currency_id'    => (string) $currencyId,
                'iso_code'       => $currency?->iso_code ?? 'N/D',
                'symbol'         => $currency?->symbol ?? '',
                'bingo_income'   => round((float) ($bingoByCurrency[$currencyId] ?? 0), 2),
                'trivia_income'  => round((float) ($triviaByCurrency[$currencyId] ?? 0), 2),
            ];
        })->sortByDesc(fn ($row) => $row['bingo_income'] + $row['trivia_income'])->values()->all();
    }

    /**
     * Volumen de venta de Bingo por Comercializadora — resuelto en PHP (no
     * SQL puro) porque la relación creator_user_id → Seller → Commerce
     * requiere Seller::resolveCommerce(), que contempla tanto reporte directo
     * (manager_user_id) como vía Subsidiary (ver comentario en Seller.php).
     * El costo es acotado: se agrupa primero en SQL por creator_user_id (no
     * por fila individual), así que el número de resoluciones en PHP es como
     * mucho el número de creadores de sala distintos, no el de ventas.
     */
    private function byCommerce(): array
    {
        $byCreator = BingoCreatorSale::query()
            ->select('creator_user_id', DB::raw('SUM(sale_amount) AS income'))
            ->groupBy('creator_user_id')
            ->pluck('income', 'creator_user_id');

        if ($byCreator->isEmpty()) {
            return [];
        }

        $sellers = Seller::whereIn('user_id', $byCreator->keys())
            ->with(['managerCommerce:id,name', 'subsidiary.commerce:id,name'])
            ->get()
            ->keyBy('user_id');

        $totals = [];
        foreach ($byCreator as $creatorUserId => $income) {
            $seller   = $sellers->get($creatorUserId);
            $commerce = $seller?->resolveCommerce();
            $key      = $commerce ? (string) $commerce->id : 'sin_comercializadora';
            $label    = $commerce?->name ?? 'Sin comercializadora asignada';

            $totals[$key] ??= ['commerce_id' => $key, 'commerce_name' => $label, 'income' => 0.0];
            $totals[$key]['income'] += (float) $income;
        }

        foreach ($totals as &$row) {
            $row['income'] = round($row['income'], 2);
        }

        return collect($totals)->sortByDesc('income')->values()->all();
    }

    private function totals(): array
    {
        return [
            'total_income'        => round((float) BingoCreatorSale::sum('sale_amount') + (float) WalletMovement::query()->where('type', WalletMovement::TYPE_TRIVIA_ENTRY)->where('status', WalletMovement::STATUS_COMPLETED)->sum('amount'), 2),
            'total_prizes_paid'   => round((float) WalletMovement::query()->whereIn('type', [WalletMovement::TYPE_PRIZE, WalletMovement::TYPE_BONUS_PRIZE])->where('status', WalletMovement::STATUS_COMPLETED)->sum('amount'), 2),
            'total_deposits'      => round((float) WalletMovement::query()->where('type', WalletMovement::TYPE_DEPOSIT)->where('status', WalletMovement::STATUS_COMPLETED)->sum('amount'), 2),
            'total_withdrawals'   => round((float) WalletMovement::query()->where('type', WalletMovement::TYPE_WITHDRAWAL)->where('status', WalletMovement::STATUS_COMPLETED)->sum('amount'), 2),
            'active_players_7d'   => User::whereHas('gameParticipations', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))->count(),
        ];
    }
}

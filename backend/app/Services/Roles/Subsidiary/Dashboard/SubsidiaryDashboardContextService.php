<?php

namespace App\Services\Roles\Subsidiary\Dashboard;

use App\DTOs\Roles\Subsidiary\Dashboard\SubsidiaryDashboardContextDTO;
use App\Domains\Wallet\Contracts\WalletRepositoryInterface;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Models\Roles\Seller;
use App\Models\Roles\Subsidiary;
use App\Domains\Wallet\Models\WalletMovement;
use App\Services\Roles\Dashboard\Concerns\BuildsSalesTrend;
use App\Services\Roles\Dashboard\Concerns\BuildsWeeklyTrend;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Contexto del dashboard de Subsidiary (agencia/sucursal) — a diferencia de
 * Commerce (crea salas, BingoCard.creator_user_id es su propio user_id) y
 * de Seller (vende directo, seller_user_id es su propio user_id), Subsidiary
 * no crea salas ni vende directamente: sus "ventas" son las agregadas de
 * SUS PROPIOS Sellers (whereIn seller_user_id). Un solo nivel — Subsidiary
 * ES la agencia, no tiene sub-agencias, a diferencia de Commerce.
 */
final class SubsidiaryDashboardContextService
{
    use BuildsSalesTrend;
    use BuildsWeeklyTrend;

    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
    ) {}

    public function dashboardContext(): SubsidiaryDashboardContextDTO
    {
        $user   = Auth::user();
        $userId = (string) $user?->getKey();
        $now    = Carbon::now();
        $period = ucfirst($now->translatedFormat('F Y'));

        $subsidiary = Subsidiary::where('user_id', $userId)->first(['id', 'commerce_id', 'commission']);
        $sellerIds  = $subsidiary ? $this->sellerUserIds($subsidiary->id) : [];

        return new SubsidiaryDashboardContextDTO(
            userName:           $user?->first_name ?? $user?->username ?? 'Agencia',
            stats:              $this->buildStats($sellerIds, $subsidiary, $now),
            recentActivity:     $this->buildRecentActivity($sellerIds),
            reportsTo:          $this->buildReportsTo($subsidiary),
            salesTrend:         $this->buildSalesTrend(scope: fn ($q) => $q->whereIn('seller_user_id', $sellerIds)),
            salesBySeller:      $this->buildSalesBySeller($sellerIds, $now),
            wallets:            $this->buildWallets($userId),
            pendingWalletCount: WalletMovement::forUser($userId)->where('status', WalletMovement::STATUS_PENDING)->count(),
            period:             $period,
        );
    }

    // -------------------------------------------------------------------------
    // Árbol de vendedores propios
    // -------------------------------------------------------------------------

    /** @return string[] */
    private function sellerUserIds(string $subsidiaryId): array
    {
        return Seller::where('subsidiary_id', $subsidiaryId)
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    // -------------------------------------------------------------------------
    // A quién reporta: Commerce padre
    // -------------------------------------------------------------------------

    private function buildReportsTo(?Subsidiary $subsidiary): ?array
    {
        if (!$subsidiary) {
            return null;
        }

        $commerce = $subsidiary->commerce;

        if (!$commerce) {
            return null;
        }

        return ['name' => $commerce->name, 'type' => 'commerce'];
    }

    // -------------------------------------------------------------------------
    // Stats principales
    // -------------------------------------------------------------------------

    private function buildStats(array $sellerIds, ?Subsidiary $subsidiary, Carbon $now): array
    {
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfDay   = $now->copy()->startOfDay();

        $lotsThisMonth = BingoCardLot::whereIn('seller_user_id', $sellerIds)
            ->where('created_at', '>=', $startOfMonth);
        $totalSalesThisMonth = (clone $lotsThisMonth)->sum('sale_amount');

        $cardsSoldToday = BingoCard::whereIn('seller_user_id', $sellerIds)
            ->where('created_at', '>=', $startOfDay)
            ->count();

        $winnersToday = BingoCard::whereIn('seller_user_id', $sellerIds)
            ->winners()
            ->where('updated_at', '>=', $startOfDay)
            ->count();

        return [
            'totalSales' => array_merge([
                'label'   => 'Ventas del mes',
                'value'   => number_format((float) $totalSalesThisMonth, 2, ',', '.'),
                'icon'    => 'fas fa-shopping-cart',
                'variant' => 'primary',
                'href'    => '/bingo/sales-report',
            ], $this->buildWeeklyTrend(BingoCardLot::whereIn('seller_user_id', $sellerIds))),
            'cardsSoldToday' => [
                'label'   => 'Cartones vendidos hoy',
                'value'   => $cardsSoldToday,
                'icon'    => 'fas fa-ticket-alt',
                'variant' => 'success',
                'href'    => '/bingo/cards-sold',
            ],
            'winnersToday' => [
                'label'   => 'Ganadores hoy',
                'value'   => $winnersToday,
                'icon'    => 'fas fa-trophy',
                'variant' => 'warning',
                'href'    => '/bingo/winners/private/today',
            ],
            'sellersCount' => [
                'label'   => 'Vendedores',
                'value'   => count($sellerIds),
                'icon'    => 'fas fa-user-tie',
                'variant' => 'info',
                'href'    => '/sellers',
            ],
            'commission' => [
                'label'       => 'Mi comisión',
                'value'       => $subsidiary ? number_format((float) $subsidiary->commission, 2) . '%' : '—',
                'icon'        => 'fas fa-percentage',
                'variant'     => 'secondary',
                'description' => 'Tasa configurada',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Ventas agrupadas por vendedor (top 10, este mes)
    // -------------------------------------------------------------------------

    private function buildSalesBySeller(array $sellerIds, Carbon $now): array
    {
        $startOfMonth = $now->copy()->startOfMonth();

        return BingoCardLot::query()
            ->whereIn('seller_user_id', $sellerIds)
            ->where('created_at', '>=', $startOfMonth)
            ->select([
                'seller_user_id',
                DB::raw('SUM(sale_amount) as total_sales'),
                DB::raw('SUM(card_count) as total_cards'),
                // Mismo criterio que CommerceDashboardContextService::
                // buildSalesBySeller() — commission_amount ya es el monto
                // persistido por lote, no una re-derivación de una tasa.
                DB::raw('SUM(commission_amount) as total_commission'),
            ])
            ->groupBy('seller_user_id')
            ->orderByDesc(DB::raw('SUM(sale_amount)'))
            ->limit(10)
            ->with('seller:id,first_name,last_name,username')
            ->get()
            ->map(function (BingoCardLot $row) {
                $sellerName = $row->seller
                    ? trim("{$row->seller->first_name} {$row->seller->last_name}") ?: $row->seller->username
                    : 'Vendedor';

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

    // -------------------------------------------------------------------------
    // Billeteras propias (saldo por moneda)
    // -------------------------------------------------------------------------

    private function buildWallets(string $userId): array
    {
        return $this->walletRepository->getActiveWalletsByUser($userId, ['id', 'currency_id', 'balance', 'bonus_balance'])
            ->load('currency:id,iso_code,symbol')
            ->map(fn ($wallet) => [
                'currency_code' => $wallet->currency?->iso_code ?? '—',
                'symbol'        => $wallet->currency?->symbol ?? '',
                'balance'       => (float) $wallet->balance,
                'bonus_balance' => (float) $wallet->bonus_balance,
            ])
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Actividad reciente: ventas de sus vendedores
    // -------------------------------------------------------------------------

    private function buildRecentActivity(array $sellerIds): array
    {
        return BingoCardLot::whereIn('seller_user_id', $sellerIds)
            ->with(['room:id,uuid_code', 'currency:id,symbol,iso_code', 'seller:id,first_name,last_name,username'])
            ->latest()
            ->limit(10)
            ->get(['id', 'room_id', 'seller_user_id', 'currency_id', 'card_count', 'sale_amount', 'status', 'created_at'])
            ->map(function (BingoCardLot $lot) {
                $sellerName = $lot->seller
                    ? trim("{$lot->seller->first_name} {$lot->seller->last_name}") ?: $lot->seller->username
                    : 'Vendedor';

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

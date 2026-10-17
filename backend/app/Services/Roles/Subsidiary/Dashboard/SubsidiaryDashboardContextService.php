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
use App\Services\Roles\Dashboard\NetworkDashboardService;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Contexto del dashboard de Subsidiary (agencia/sucursal) — a diferencia de
 * Commerce (crea salas, BingoCard.creator_user_id es su propio user_id) y
 * de Seller (vende directo, seller_user_id es su propio user_id), Subsidiary
 * no crea salas ni vende directamente: sus "ventas" son las agregadas de
 * SUS PROPIOS Sellers (whereIn seller_user_id). Un solo nivel — Subsidiary
 * ES la agencia, no tiene sub-agencias, a diferencia de Commerce.
 *
 * Montos: las ventas se normalizan a USD con la tasa de cada lote y la
 * comisión se muestra en su moneda principal — nunca se suman monedas
 * distintas (ver NetworkDashboardService).
 */
final class SubsidiaryDashboardContextService
{
    use BuildsSalesTrend;
    use BuildsWeeklyTrend;

    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly NetworkDashboardService $network,
    ) {}

    public function dashboardContext(): SubsidiaryDashboardContextDTO
    {
        $user   = Auth::user();
        $userId = (string) $user?->getKey();
        $now    = Carbon::now();
        $period = ucfirst($now->translatedFormat('F Y'));

        $subsidiary = Subsidiary::where('user_id', $userId)->first(['id', 'commerce_id', 'commission']);
        $sellers    = $subsidiary
            ? Seller::where('subsidiary_id', $subsidiary->id)->get(['id', 'user_id', 'name', 'phone', 'status'])
            : collect();
        $sellerIds  = $sellers->pluck('user_id')->map(fn ($id) => (string) $id)->all();

        return new SubsidiaryDashboardContextDTO(
            userName:           $user?->first_name ?? $user?->username ?? 'Agencia',
            stats:              $this->buildStats($sellerIds, $subsidiary, $now),
            recentActivity:     $this->buildRecentActivity($sellerIds),
            reportsTo:          $this->buildReportsTo($subsidiary),
            salesTrend:         $this->buildSalesTrend(scope: fn ($q) => $q
                ->whereIn('seller_user_id', $sellerIds)
                ->where('status', '!=', BingoCard::STATUS_CANCELLED)),
            salesBySeller:      $this->network->salesBySellerUsd(
                $this->networkLots($subsidiary, $sellerIds)->where('bingo_card_lots.created_at', '>=', $now->copy()->startOfMonth()),
            ),
            wallets:            $this->buildWallets($userId),
            pendingWalletCount: WalletMovement::forUser($userId)->where('status', WalletMovement::STATUS_PENDING)->count(),
            period:             $period,
            canManageRooms:     (bool) $user?->hasPermissionTo('games.bingo.manage'),
            networkHealth:      $this->network->networkHealth($sellers, $now),
            networkPayments:    $this->network->networkPayments($sellerIds, $now),
            prizesToDeliver:    $this->network->prizesToDeliver($sellerIds),
        );
    }

    // -------------------------------------------------------------------------
    // Ventas de la red
    // -------------------------------------------------------------------------

    /**
     * Lotes vendidos (no reembolsados) por la red de la agencia: por el
     * snapshot subsidiary_id del lote — un vendedor reasignado no arrastra
     * el histórico — y, para lotes anteriores al snapshot, por sus vendedores
     * actuales (mismo criterio que SubsidiaryProfileService).
     *
     * @param string[] $sellerIds
     */
    private function networkLots(?Subsidiary $subsidiary, array $sellerIds): Builder
    {
        if (!$subsidiary) {
            return $this->network->soldLots()->whereRaw('1 = 0');
        }

        return $this->network->soldLots()->where(fn (Builder $q) => $q
            ->where('bingo_card_lots.subsidiary_id', $subsidiary->id)
            ->orWhere(fn (Builder $legacy) => $legacy
                ->whereNull('bingo_card_lots.subsidiary_id')
                ->whereIn('bingo_card_lots.seller_user_id', $sellerIds)));
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

        $salesUsd = $this->network->salesUsd(
            $this->networkLots($subsidiary, $sellerIds)->where('bingo_card_lots.created_at', '>=', $startOfMonth),
        );

        // Cartones anulados (pago rechazado / sala cancelada) no son venta.
        $cardsSoldToday = BingoCard::whereIn('seller_user_id', $sellerIds)
            ->where('status', '!=', BingoCard::STATUS_CANCELLED)
            ->where('created_at', '>=', $startOfDay)
            ->count();

        $winnersToday = BingoCard::whereIn('seller_user_id', $sellerIds)
            ->winners()
            ->where('updated_at', '>=', $startOfDay)
            ->count();

        // Lo que la agencia GANÓ este mes (Y − X de cada venta, reparto en
        // cascada), en su moneda principal: antes se sumaban todas las monedas.
        $earned = $subsidiary
            ? $this->network->currencySummary(
                $this->network->soldLots()
                    ->where('bingo_card_lots.subsidiary_id', $subsidiary->id)
                    ->where('bingo_card_lots.created_at', '>=', $startOfMonth)
                    ->where('bingo_card_lots.subsidiary_commission_amount', '>', 0),
                'subsidiary_commission_amount',
            )
            : ['count' => 0, 'amount' => null];

        return [
            'totalSales' => array_merge([
                'label'       => 'Ventas del mes',
                'value'       => 'US$ ' . number_format($salesUsd, 2, ',', '.'),
                'icon'        => 'fas fa-shopping-cart',
                'variant'     => 'primary',
                'description' => 'En USD, a la tasa de cada venta',
                'href'        => '/bingo/sales-report',
            ], $this->buildWeeklyTrend($this->networkLots($subsidiary, $sellerIds))),
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
                'href'    => '/bingo/winners',
            ],
            'sellersCount' => [
                'label'   => 'Vendedores',
                'value'   => count($sellerIds),
                'icon'    => 'fas fa-user-tie',
                'variant' => 'info',
                'href'    => '/sellers',
            ],
            'commission' => [
                'label'       => 'Mi comisión del mes',
                'value'       => $earned['amount'] ?? '—',
                'icon'        => 'fas fa-percentage',
                'variant'     => 'secondary',
                'description' => $subsidiary
                    ? 'Tasa ' . number_format((float) $subsidiary->commission, 2) . '% (diferencia sobre la de cada vendedor)'
                    : 'Tasa configurada',
            ],
        ];
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

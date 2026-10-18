<?php

namespace App\Services\Roles\Commerce\Dashboard;

use App\DTOs\Roles\Commerce\Dashboard\CommerceDashboardContextDTO;
use App\Domains\Wallet\Contracts\WalletRepositoryInterface;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Domains\Wallet\Models\WalletMovement;
use App\Services\Roles\Dashboard\Concerns\BuildsSalesTrend;
use App\Services\Roles\Dashboard\Concerns\BuildsWeeklyTrend;
use App\Services\Roles\Dashboard\NetworkDashboardService;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Contexto del dashboard de Commerce (comercializadora) — dueño de las
 * salas que crea (BingoRoom.creator_user_id = su user_id) y de la red de
 * vendedores/agencias que operan bajo su cuenta.
 *
 * Montos: las ventas se normalizan a USD con la tasa de cada lote y la
 * comisión se muestra en su moneda principal — nunca se suman monedas
 * distintas (ver NetworkDashboardService).
 */
final class CommerceDashboardContextService
{
    use BuildsSalesTrend;
    use BuildsWeeklyTrend;

    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly NetworkDashboardService $network,
    ) {}

    public function dashboardContext(): CommerceDashboardContextDTO
    {
        $user   = Auth::user();
        $userId = (string) $user?->getKey();
        $now    = Carbon::now();
        $period = ucfirst($now->translatedFormat('F Y'));

        $commerce = Commerce::where('user_id', $userId)->first(['id', 'user_id', 'commission']);

        // Toda la red del commerce (él mismo + vendedores directos + los de
        // cada agencia/subsidiary) — antes estos widgets solo contaban lo
        // que el commerce vendía con su PROPIA cuenta (BingoCardLot.user_id
        // = creador de sala), así que un commerce con vendedores activos
        // veía un total de ventas/comisión incompleto: nunca sumaba lo
        // generado por su red. Ver Commerce::networkUserIds().
        //
        // pendingWalletCount/wallets siguen siendo estrictamente personales
        // ($userId, no $networkUserIds): cada vendedor tiene su propia
        // wallet con sus propios movimientos, no relacionados a la del
        // commerce — mezclarlos sería sumar dinero que no es del commerce.
        $networkUserIds = $commerce ? $commerce->networkUserIds() : [$userId];

        return new CommerceDashboardContextDTO(
            userName:           $user?->first_name ?? $user?->username ?? 'Comercio',
            stats:              $this->buildStats($userId, $networkUserIds, $commerce, $now),
            recentActivity:     $this->buildRecentActivity($networkUserIds),
            entityCounts:       $this->buildEntityCounts($commerce),
            salesTrend:         $this->buildSalesTrend(scope: fn ($q) => $q
                ->whereIn('creator_user_id', $networkUserIds)
                ->where('status', '!=', BingoCard::STATUS_CANCELLED)),
            salesBySeller:      $this->network->salesBySellerUsd(
                $this->networkLots($commerce, $networkUserIds)->where('bingo_card_lots.created_at', '>=', $now->copy()->startOfMonth()),
                'Venta directa',
            ),
            wallets:            $this->buildWallets($userId),
            configuration:      $this->buildConfiguration($commerce),
            pendingWalletCount: WalletMovement::forUser($userId)->where('status', WalletMovement::STATUS_PENDING)->count(),
            period:             $period,
            // Deuda RKE pendiente con la plataforma (ventas cuyo dinero cobró el
            // comercio): la ve para saber cuánto tiene que liquidar.
            rkeReceivables:     app(\App\Services\Platform\PlatformReceivableService::class)->pendingSummary($userId),
            networkHealth:      $this->network->networkHealth($this->networkSellers($commerce), $now),
            // El comercio es quien revisa estos pagos (/bingo/card-payments,
            // mismo alcance: vendedores de su red).
            networkPayments:    $this->network->networkPayments($networkUserIds, $now),
            prizesToDeliver:    $this->network->prizesToDeliver($networkUserIds),
            upcomingRooms:      $commerce ? $this->network->upcomingOwnRooms($now) : [],
        );
    }

    // -------------------------------------------------------------------------
    // Ventas y vendedores de la red
    // -------------------------------------------------------------------------

    /**
     * Lotes vendidos (no reembolsados) por la red: por el snapshot
     * commerce_id del lote y, para lotes anteriores al snapshot, por
     * user_id (la comercializadora del vendedor al vender).
     *
     * @param string[] $networkUserIds
     */
    private function networkLots(?Commerce $commerce, array $networkUserIds): Builder
    {
        return $this->network->soldLots()->where(function (Builder $q) use ($commerce, $networkUserIds) {
            if ($commerce) {
                $q->where('bingo_card_lots.commerce_id', $commerce->id);
            }

            $q->orWhere(fn (Builder $legacy) => $legacy
                ->whereNull('bingo_card_lots.commerce_id')
                ->whereIn('bingo_card_lots.user_id', $networkUserIds));
        });
    }

    /** Vendedores directos + los de sus agencias. */
    private function networkSellers(?Commerce $commerce): \Illuminate\Support\Collection
    {
        if (!$commerce) {
            return collect();
        }

        return Seller::query()
            ->where(fn (Builder $q) => $q
                ->where('manager_user_id', $commerce->user_id)
                ->orWhereIn('subsidiary_id', $commerce->subsidiaries()->pluck('id')))
            ->get(['id', 'user_id', 'name', 'phone', 'status']);
    }

    // -------------------------------------------------------------------------
    // Stats principales
    // -------------------------------------------------------------------------

    /** @param string[] $networkUserIds */
    private function buildStats(string $userId, array $networkUserIds, ?Commerce $commerce, Carbon $now): array
    {
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfDay   = $now->copy()->startOfDay();

        $salesUsd = $this->network->salesUsd(
            $this->networkLots($commerce, $networkUserIds)->where('bingo_card_lots.created_at', '>=', $startOfMonth),
        );
        // Cartones anulados (pago rechazado / sala cancelada) no son venta.
        $cardsSoldToday = BingoCard::whereIn('creator_user_id', $networkUserIds)
            ->where('status', '!=', BingoCard::STATUS_CANCELLED)
            ->where('created_at', '>=', $startOfDay)
            ->count();
        $activeRooms = BingoRoom::active()->whereIn('creator_user_id', $networkUserIds)->count();
        $pendingPayouts = WalletMovement::forUser($userId)
            ->where('status', WalletMovement::STATUS_PENDING)
            ->count();
        $commissionByCurrency = $this->buildCommissionThisMonth($networkUserIds, $startOfMonth, $commerce);

        return [
            'totalSales' => array_merge([
                'label'       => 'Ventas del mes',
                'value'       => 'US$ ' . number_format($salesUsd, 2, ',', '.'),
                'icon'        => 'fas fa-shopping-cart',
                'variant'     => 'primary',
                'description' => 'En USD, a la tasa de cada venta',
                'href'        => '/bingo/sales-report',
            ], $this->buildWeeklyTrend($this->networkLots($commerce, $networkUserIds))),
            'totalRevenue' => [
                'label'   => 'Cartones vendidos hoy',
                'value'   => $cardsSoldToday,
                'icon'    => 'fas fa-ticket-alt',
                'variant' => 'success',
            ],
            'activeGames' => [
                'label'   => 'Salas activas',
                'value'   => $activeRooms,
                'icon'    => 'fas fa-dice',
                'variant' => 'info',
                'href'    => '/bingo-rooms',
            ],
            'pendingPayouts' => [
                'label'   => 'Movimientos pendientes',
                'value'   => $pendingPayouts,
                'icon'    => 'fas fa-clock',
                'variant' => 'warning',
                'href'    => '/wallet-movements',
            ],
            'commission' => [
                'label'       => 'Mi comisión (este mes)',
                'value'       => $commissionByCurrency['primaryLabel'],
                'icon'        => 'fas fa-percentage',
                'variant'     => 'secondary',
                // El prefijo "Tasa X%" solo se antepone cuando SÍ hubo
                // comisión que reportar este mes — mostrarlo junto a "Sin
                // comisión este mes" es información redundante que no aporta
                // nada cuando no hubo ventas.
                'description' => ($commerce && $commissionByCurrency['hasCommission'])
                    ? sprintf('Tasa %s%% · %s', number_format((float) $commerce->commission, 2), $commissionByCurrency['descriptionSuffix'])
                    : $commissionByCurrency['descriptionSuffix'],
            ],
        ];
    }

    /**
     * Comisión de SALA ganada este mes por el comercio en sus salas
     * (bingo_creator_sales.room_commission, acumulada por RoomMetricsService
     * en moneda local), agrupada por moneda.
     *
     * Antes se leía BingoCardLot.commission_amount asumiendo que era la
     * comisión de sala — pero esa columna nunca se escribía (siempre 0) y
     * ahora guarda la comisión del VENDEDOR de cada lote (lo que el comercio
     * paga, no lo que gana). La comisión de sala por venta vive en
     * bingo_creator_sales, que es su fuente real.
     *
     * Suma además la porción de RED del comercio (commerce_commission_amount
     * de los lotes vendidos por su red, reparto en cascada — ver
     * CommissionCalculationService::networkSplit()), en cualquier sala: son
     * dos ingresos distintos, como organizador y como distribuidor.
     *
     * @param string[] $networkUserIds
     * @return array{primaryLabel: string, descriptionSuffix: string, hasCommission: bool}
     */
    private function buildCommissionThisMonth(array $networkUserIds, Carbon $startOfMonth, ?Commerce $commerce = null): array
    {
        // Alias distinto de "total_commission": BingoCreatorSale tiene un
        // accessor getTotalCommissionAttribute() que lo taparía (y en una
        // fila agregada devuelve 0).
        $byCurrency = \App\Domains\Games\Bingo\Models\Creator\BingoCreatorSale::whereIn('creator_user_id', $networkUserIds)
            ->where('updated_at', '>=', $startOfMonth)
            ->groupBy('currency_id')
            ->pluck(DB::raw('SUM(room_commission) as room_commission_sum'), 'currency_id')
            ->map(fn ($sum) => (float) $sum);

        if ($commerce) {
            BingoCardLot::where('commerce_id', $commerce->id)
                ->where('status', 'ACTIVE')
                ->where('created_at', '>=', $startOfMonth)
                ->groupBy('currency_id')
                ->pluck(DB::raw('SUM(commerce_commission_amount) as network_sum'), 'currency_id')
                ->each(function ($sum, $currencyId) use ($byCurrency) {
                    $byCurrency[$currencyId] = ($byCurrency[$currencyId] ?? 0) + (float) $sum;
                });
        }

        // Venta cruzada: la comisión de sala que su red cobró en salas PÚBLICAS
        // ajenas no es suya (se la debe al creador, PlatformReceivable.
        // creator_amount); la que otras redes cobraron en SUS salas sí lo es.
        if ($commerce) {
            $receivables = \App\Models\Platform\PlatformReceivable::query()
                ->where('status', '!=', \App\Models\Platform\PlatformReceivable::STATUS_CANCELLED)
                ->where('created_at', '>=', $startOfMonth)
                ->where('creator_amount', '>', 0);

            (clone $receivables)->where('debtor_user_id', $commerce->user_id)
                ->groupBy('currency_id')
                ->pluck(DB::raw('SUM(creator_amount) as custody_sum'), 'currency_id')
                ->each(function ($sum, $currencyId) use ($byCurrency) {
                    $byCurrency[$currencyId] = ($byCurrency[$currencyId] ?? 0) - (float) $sum;
                });

            (clone $receivables)->where('creator_payee_user_id', $commerce->user_id)
                ->groupBy('currency_id')
                ->pluck(DB::raw('SUM(creator_amount) as owed_sum'), 'currency_id')
                ->each(function ($sum, $currencyId) use ($byCurrency) {
                    $byCurrency[$currencyId] = ($byCurrency[$currencyId] ?? 0) + (float) $sum;
                });
        }

        $byCurrency = $byCurrency->filter(fn ($sum) => $sum > 0)->sortDesc();

        if ($byCurrency->isEmpty()) {
            return ['primaryLabel' => '—', 'descriptionSuffix' => 'Sin comisión este mes', 'hasCommission' => false];
        }

        $primaryCurrency = \App\Models\Currency::find($byCurrency->keys()->first(), ['id', 'symbol']);
        $primaryLabel = ($primaryCurrency?->symbol ?? '') . ' ' . number_format((float) $byCurrency->first(), 2);

        $rest = $byCurrency->slice(1);
        $descriptionSuffix = $rest->isEmpty()
            ? 'Tasa configurada'
            : 'Tasa configurada · +' . $rest->count() . ' moneda(s) más';

        return ['primaryLabel' => $primaryLabel, 'descriptionSuffix' => $descriptionSuffix, 'hasCommission' => true];
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
    // Monedas y juegos habilitados
    // -------------------------------------------------------------------------

    private function buildConfiguration(?Commerce $commerce): array
    {
        if (!$commerce) {
            return ['currencies' => [], 'games' => []];
        }

        return [
            'currencies' => $commerce->currenciesActive()->get(['currencies.id', 'iso_code', 'symbol'])
                ->map(fn ($c) => ['code' => $c->iso_code, 'symbol' => $c->symbol])
                ->values()
                ->all(),
            'games' => $commerce->gamesActive()->get(['games.id', 'name'])
                ->pluck('name')
                ->values()
                ->all(),
        ];
    }

    // -------------------------------------------------------------------------
    // Red propia: vendedores directos + agencias/sucursales
    // -------------------------------------------------------------------------

    private function buildEntityCounts(?Commerce $commerce): array
    {
        if (!$commerce) {
            return [];
        }

        // sellers() solo resuelve reporte directo (manager_user_id); los
        // vendedores que reportan vía una Subsidiary no tienen commerce_id
        // propio en `sellers` (ver Seller::resolveCommerce()), así que se
        // cuentan por separado a través de las subsidiarias del commerce.
        $directSellers = $commerce->sellers()->count();
        $subsidiaries  = $commerce->subsidiaries()->count();
        $subsidiarySellers = $commerce->subsidiaries()
            ->withCount('sellers')
            ->get()
            ->sum('sellers_count');

        return [
            array_merge([
                'label' => 'Vendedores directos', 'value' => $directSellers, 'icon' => 'fas fa-user-tie',
                // /sellers (no /admin/sellers, que es solo admin/superadmin).
                'variant' => 'success', 'href' => '/sellers',
            ], $this->buildWeeklyTrend(Seller::where('manager_user_id', $commerce->user_id))),
            [
                'label' => 'Agencias/Sucursales', 'value' => $subsidiaries, 'icon' => 'fas fa-code-branch',
                'variant' => 'info', 'href' => '/admin/subsidiaries',
            ],
            [
                'label' => 'Vendedores en agencias', 'value' => $subsidiarySellers, 'icon' => 'fas fa-users',
                'variant' => 'secondary',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Actividad reciente: ventas de la red propia
    // -------------------------------------------------------------------------

    /** @param string[] $networkUserIds */
    private function buildRecentActivity(array $networkUserIds): array
    {
        return BingoCardLot::whereIn('user_id', $networkUserIds)
            ->with(['room:id,uuid_code', 'currency:id,symbol,iso_code', 'seller:id,first_name,last_name,username'])
            ->latest()
            ->limit(10)
            ->get(['id', 'room_id', 'seller_user_id', 'currency_id', 'card_count', 'sale_amount', 'status', 'created_at'])
            ->map(function (BingoCardLot $lot) {
                $sellerName = $lot->seller
                    ? trim("{$lot->seller->first_name} {$lot->seller->last_name}") ?: $lot->seller->username
                    : 'Venta directa';

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

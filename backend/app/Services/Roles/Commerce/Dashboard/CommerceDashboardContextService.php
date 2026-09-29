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
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Contexto del dashboard de Commerce (comercializadora) — dueño de las
 * salas que crea (BingoRoom.creator_user_id = su user_id) y de la red de
 * vendedores/agencias que operan bajo su cuenta.
 */
final class CommerceDashboardContextService
{
    use BuildsSalesTrend;
    use BuildsWeeklyTrend;

    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
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
            salesTrend:         $this->buildSalesTrend(scope: fn ($q) => $q->whereIn('creator_user_id', $networkUserIds)),
            salesBySeller:      $this->buildSalesBySeller($networkUserIds, $now),
            wallets:            $this->buildWallets($userId),
            configuration:      $this->buildConfiguration($commerce),
            pendingWalletCount: WalletMovement::forUser($userId)->where('status', WalletMovement::STATUS_PENDING)->count(),
            period:             $period,
            // Deuda RKE pendiente con la plataforma (ventas cuyo dinero cobró el
            // comercio): la ve para saber cuánto tiene que liquidar.
            rkeReceivables:     app(\App\Services\Platform\PlatformReceivableService::class)->pendingSummary($userId),
        );
    }

    // -------------------------------------------------------------------------
    // Stats principales
    // -------------------------------------------------------------------------

    /** @param string[] $networkUserIds */
    private function buildStats(string $userId, array $networkUserIds, ?Commerce $commerce, Carbon $now): array
    {
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfDay   = $now->copy()->startOfDay();

        $lotsThisMonth = BingoCardLot::whereIn('user_id', $networkUserIds)
            ->where('created_at', '>=', $startOfMonth);

        $totalSalesThisMonth = (clone $lotsThisMonth)->sum('sale_amount');
        $cardsSoldToday      = BingoCard::whereIn('creator_user_id', $networkUserIds)
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
                'value'       => number_format((float) $totalSalesThisMonth, 2, ',', '.'),
                'icon'        => 'fas fa-shopping-cart',
                'variant'     => 'primary',
                'href'        => '/bingo/sales-report',
            ], $this->buildWeeklyTrend(BingoCardLot::whereIn('user_id', $networkUserIds))),
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
    // Ventas agrupadas por vendedor (top 10, este mes)
    // -------------------------------------------------------------------------

    /** @param string[] $networkUserIds */
    private function buildSalesBySeller(array $networkUserIds, Carbon $now): array
    {
        $startOfMonth = $now->copy()->startOfMonth();

        return BingoCardLot::query()
            ->whereIn('user_id', $networkUserIds)
            ->where('created_at', '>=', $startOfMonth)
            ->select([
                'seller_user_id',
                DB::raw('SUM(sale_amount) as total_sales'),
                DB::raw('SUM(card_count) as total_cards'),
                // commission_amount = comisión del VENDEDOR persistida por
                // lote (ver BingoCardGenerationService::generateCards()).
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
                'variant' => 'success', 'href' => '/admin/sellers',
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

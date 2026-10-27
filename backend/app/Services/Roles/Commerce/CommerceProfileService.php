<?php

namespace App\Services\Roles\Commerce;

use App\Models\Currency;
use App\Models\Platform\PlatformReceivable;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Roles\Subsidiary;
use App\Services\Roles\AdminProfileAccessService;
use App\Services\Roles\RoleProfileEnrichmentService;
use App\Services\Roles\RoleProfileInsightsService as Insights;
use App\Services\Security\UserContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Autogestión de la ficha propia de un Commerce (comercializadora) —
 * distinto de AdminCommerceService (gestión de terceros por admin,
 * protegido por commerces.*). Por defecto SIEMPRE resuelve el registro
 * Commerce del usuario autenticado; admin/superadmin pueden pasar
 * $targetUserId (resuelto por AdminProfileAccessService) para inspeccionar
 * la ficha de un comercio específico — sin esto, "Mi Comercio" en el
 * sidebar era un 404 garantizado para admin/superadmin (no tienen registro
 * Commerce propio).
 */
final class CommerceProfileService
{
    private const NETWORK_LIST_LIMIT = 8;
    private const TOP_SELLERS_LIMIT = 5;

    public function __construct(
        private readonly UserContextService $userContext,
        private readonly AdminProfileAccessService $adminAccess,
        private readonly RoleProfileEnrichmentService $enrichment,
        private readonly Insights $insights,
    ) {
    }

    public function showOwn(?string $targetUserId = null) : array
    {
        $commerce = $this->ownCommerceOrFail($targetUserId);
        $commerce->load(['country:id,name', 'user:id,username,email']);

        $rate = (float) $commerce->commission;
        $networkUserIds = $commerce->networkUserIds();

        $subsidiaries = Subsidiary::where('commerce_id', $commerce->id)
            ->where('status', '!=', 'DELETED')
            ->withCount(['sellers' => fn ($q) => $q->where('status', '!=', 'DELETED')])
            ->orderBy('name')
            ->get(['id', 'name', 'commission', 'status']);

        $directSellers = Seller::where('manager_user_id', $commerce->user_id)
            ->whereNull('subsidiary_id')
            ->where('status', '!=', 'DELETED')
            ->get(['id', 'name', 'commission', 'status', 'user_id']);

        $sellersCount = $commerce->sellers()->where('status', '!=', 'DELETED')->count();

        // Ventas "suyas": las de su red. commerce_id es el snapshot que guarda
        // cada lote al vender (reparto en cascada); los lotes previos al
        // snapshot (commerce_id null) se atribuyen por los vendedores actuales.
        $lotScope = fn (Builder $q) => $q
            ->where('commerce_id', $commerce->id)
            ->orWhere(fn (Builder $legacy) => $legacy->whereNull('commerce_id')->whereIn('seller_user_id', $networkUserIds));

        return [
            'role' => 'commerce',
            'id' => $commerce->hashed_id,
            'name' => $commerce->name,
            'phone' => $commerce->phone,
            'status' => $commerce->status,
            'status_badge' => $commerce->status_badge,
            'country' => $commerce->country?->name,
            'user' => ['username' => $commerce->user->username, 'email' => $commerce->user->email],
            'commission' => $rate,

            'network' => [
                'subsidiaries_count' => $subsidiaries->count(),
                'sellers_count' => $sellersCount,
                'direct_sellers_count' => $directSellers->count(),
                'subsidiaries' => $this->subsidiaryRows($subsidiaries),
                'top_sellers' => $this->topSellers($networkUserIds),
            ],

            // Regla de cobro en cascada: la comercializadora cobra Z − max(X, Y)
            // sobre cada venta de su red (CommissionCalculationService::networkSplit()).
            'hierarchy' => [
                'chain' => [
                    ['level' => 'commerce', 'name' => $commerce->name, 'rate' => $rate, 'status' => $commerce->status, 'is_self' => true],
                ],
                'children_rate_range' => $this->rateRange(
                    $subsidiaries->pluck('commission')->filter(fn ($r) => (float) $r > 0)
                        ->merge($directSellers->pluck('commission')),
                ),
            ],

            'currencies' => $commerce->currenciesActive()->pluck('iso_code')->all(),
            'games' => $commerce->gamesActive()->pluck('name')->all(),

            'performance' => $this->insights->performance($lotScope, 'commerce_commission_amount'),
            'receivables' => $this->pendingReceivables((string) $commerce->user_id),
            'alerts' => $this->alerts($commerce, $subsidiaries, $directSellers, $sellersCount),
            'security' => $this->insights->security((string) $commerce->user_id),
            'wallets' => $this->enrichment->wallets($commerce->user),
            'recent_movements' => $this->enrichment->recentMovements($commerce->user),
            'account_meta' => $this->enrichment->accountMeta((string) $commerce->user_id),
        ];
    }

    /**
     * Solo estos 2 campos son autogestionables — comisión/monedas/juegos
     * nunca llegan aquí (array_intersect_key es defensa en profundidad,
     * aunque el Request ya filtre los campos aceptados). admin/superadmin
     * pueden editar vía $targetUserId igual que showOwn().
     *
     * @param array{name?: string, phone?: string} $data
     */
    public function updateOwn(array $data, ?string $targetUserId = null) : void
    {
        $commerce = $this->ownCommerceOrFail($targetUserId);
        $commerce->update(array_intersect_key($data, array_flip(['name', 'phone'])));
    }

    private function alerts(Commerce $commerce, $subsidiaries, $directSellers, int $sellersCount) : array
    {
        $alerts = [];
        $rate = (float) $commerce->commission;

        if ($commerce->status !== 'ACTIVE') {
            $alerts[] = Insights::alert(
                'danger',
                'fa-ban',
                'Comercio no activo',
                'Tu comercializadora está ' . strtolower($commerce->status) . ': ni tú ni tu red pueden vender. Contacta a soporte.',
            );
        }

        $agenciesAbove = $subsidiaries->filter(fn ($s) => (float) $s->commission > $rate + 0.001)->count();
        $sellersAbove = $directSellers->filter(fn ($s) => (float) $s->commission > $rate + 0.001)->count();

        if ($agenciesAbove + $sellersAbove > 0) {
            $alerts[] = Insights::alert(
                'warning',
                'fa-scale-unbalanced',
                'Comisiones por encima de la tuya',
                sprintf('%d agencia(s) y %d vendedor(es) directo(s) tienen comisión mayor a tu %s%%: no recibes margen sobre sus ventas.', $agenciesAbove, $sellersAbove, rtrim(rtrim(number_format($rate, 2), '0'), '.')),
                'Revisar vendedores',
                '/sellers',
            );
        }

        if ($sellersCount === 0) {
            $alerts[] = Insights::alert(
                'info',
                'fa-user-plus',
                'Aún no tienes vendedores',
                'Crea tu primer vendedor (taquilla) para empezar a vender cartones a jugadores.',
                'Crear vendedor',
                '/sellers',
            );
        }

        return $alerts;
    }

    private function ownCommerceOrFail(?string $targetUserId = null) : Commerce
    {
        $actorId = $this->userContext->resolveUserId();
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        $commerce = Commerce::where('user_id', $access['owner_user_id'])->first();

        abort_if(! $commerce, 404, 'No se encontró un comercio asociado a este usuario.');

        return $commerce;
    }

    /**
     * Deuda pendiente con la plataforma por ventas fuera del sistema (RKE +
     * jackpot + bote que el comercio cobró en mano — ver PlatformReceivable).
     */
    private function pendingReceivables(string $userId) : array
    {
        $rows = PlatformReceivable::where('debtor_user_id', $userId)
            ->where('status', PlatformReceivable::STATUS_PENDING)
            ->groupBy('currency_id')
            ->get(['currency_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as items')]);

        $currencies = Currency::whereIn('id', $rows->pluck('currency_id'))->get(['id', 'iso_code', 'symbol'])->keyBy(fn ($c) => (string) $c->id);

        return $rows->map(fn ($row) => [
            'currency' => $currencies[(string) $row->currency_id]->iso_code ?? '—',
            'symbol' => $currencies[(string) $row->currency_id]->symbol ?? '',
            'amount' => round((float) $row->total, 2),
            'items' => (int) $row->items,
        ])->values()->all();
    }

    private function rateRange($rates) : ?array
    {
        $rates = collect($rates)->map(fn ($r) => (float) $r);

        return $rates->isEmpty() ? null : ['min' => $rates->min(), 'max' => $rates->max()];
    }

    // -------------------------------------------------------------------------

    private function subsidiaryRows($subsidiaries) : array
    {
        $sellerUserIdsBySubsidiary = Seller::whereIn('subsidiary_id', $subsidiaries->pluck('id'))
            ->get(['subsidiary_id', 'user_id'])
            ->groupBy(fn ($s) => (string) $s->subsidiary_id)
            ->map(fn ($group) => $group->pluck('user_id')->map(fn ($id) => (string) $id)->all());

        $monthCards = $this->insights->monthCardsBySeller($sellerUserIdsBySubsidiary->flatten()->all());

        return $subsidiaries
            ->map(fn (Subsidiary $s) => [
                'name' => $s->name,
                'commission' => (float) $s->commission,
                'status' => $s->status,
                'sellers_count' => (int) $s->sellers_count,
                'month_cards' => array_sum(array_intersect_key($monthCards, array_flip($sellerUserIdsBySubsidiary[(string) $s->id] ?? []))),
            ])
            ->sortByDesc('month_cards')
            ->take(self::NETWORK_LIST_LIMIT)
            ->values()
            ->all();
    }

    /** @param string[] $networkUserIds */
    private function topSellers(array $networkUserIds) : array
    {
        $monthCards = $this->insights->monthCardsBySeller($networkUserIds);
        arsort($monthCards);
        $top = array_slice($monthCards, 0, self::TOP_SELLERS_LIMIT, true);

        if ($top === []) {
            return [];
        }

        $sellers = Seller::with('subsidiary:id,name')
            ->whereIn('user_id', array_keys($top))
            ->get(['user_id', 'name', 'subsidiary_id'])
            ->keyBy(fn ($s) => (string) $s->user_id);

        return collect($top)
            ->filter(fn ($cards, $userId) => $sellers->has($userId))
            ->map(fn ($cards, $userId) => [
                'name' => $sellers[$userId]->name,
                'subsidiary' => $sellers[$userId]->subsidiary?->name,
                'cards' => $cards,
            ])
            ->values()
            ->all();
    }
}

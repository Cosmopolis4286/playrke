<?php

namespace App\Services\Roles\Subsidiary;

use App\Models\Roles\Seller;
use App\Models\Roles\Subsidiary;
use App\Services\Roles\AdminProfileAccessService;
use App\Services\Roles\RoleProfileEnrichmentService;
use App\Services\Roles\RoleProfileInsightsService as Insights;
use App\Services\Security\UserContextService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Autogestión de la ficha propia de una Subsidiary (agencia/sucursal) —
 * distinto de AdminSubsidiaryService (gestión de terceros por admin/commerce,
 * protegido por subsidiaries.*). Por defecto SIEMPRE resuelve el registro
 * Subsidiary del usuario autenticado; admin/superadmin pueden pasar
 * $targetUserId (resuelto por AdminProfileAccessService) para inspeccionar
 * la ficha de una agencia específica — sin esto, "Mi Agencia" en el
 * sidebar era un 404 garantizado para admin/superadmin (no tienen registro
 * Subsidiary propio).
 */
final class SubsidiaryProfileService
{
    private const NETWORK_LIST_LIMIT = 10;

    public function __construct(
        private readonly UserContextService $userContext,
        private readonly AdminProfileAccessService $adminAccess,
        private readonly RoleProfileEnrichmentService $enrichment,
        private readonly Insights $insights,
    ) {
    }

    public function showOwn(?string $targetUserId = null) : array
    {
        $subsidiary = $this->ownSubsidiaryOrFail($targetUserId);
        $subsidiary->load(['commerce:id,name,status,commission', 'country:id,name', 'currency:id,name,symbol', 'user:id,username,email']);

        $rate = (float) $subsidiary->commission;
        $commerce = $subsidiary->commerce;

        // Cada nivel ve las comisiones SUYAS y de quienes tiene debajo (sus
        // vendedores), nunca la de su comercializadora. Admin ve todo.
        $isAdminView = (bool) $this->adminAccess->resolve($this->userContext->resolveUserId(), $targetUserId)['is_admin_view'];

        $sellers = Seller::where('subsidiary_id', $subsidiary->id)
            ->where('status', '!=', 'DELETED')
            ->orderBy('name')
            ->get(['id', 'name', 'commission', 'status', 'user_id']);

        $sellerUserIds = $sellers->pluck('user_id')->map(fn ($id) => (string) $id)->all();
        $monthCards = $this->insights->monthCardsBySeller($sellerUserIds);

        // subsidiary_id es el snapshot que guarda cada lote al vender; los
        // previos al snapshot se atribuyen por sus vendedores actuales.
        $lotScope = fn (Builder $q) => $q
            ->where('subsidiary_id', $subsidiary->id)
            ->orWhere(fn (Builder $legacy) => $legacy->whereNull('subsidiary_id')->whereIn('seller_user_id', $sellerUserIds));

        return [
            'role' => 'subsidiary',
            'id' => $subsidiary->hashed_id,
            'name' => $subsidiary->name,
            'phone' => $subsidiary->phone,
            'status' => $subsidiary->status,
            'status_badge' => $subsidiary->status_badge,
            'commerce' => $commerce ? ['name' => $commerce->name, 'status' => $commerce->status] : null,
            'country' => $subsidiary->country?->name,
            'currency' => $subsidiary->currency ? "{$subsidiary->currency->name} ({$subsidiary->currency->symbol})" : null,
            'user' => ['username' => $subsidiary->user->username, 'email' => $subsidiary->user->email],
            'commission' => $rate,

            'network' => [
                'sellers_count' => $sellers->count(),
                'sellers' => $sellers
                    ->map(fn (Seller $s) => [
                        'name' => $s->name,
                        'commission' => (float) $s->commission,
                        'status' => $s->status,
                        'month_cards' => $monthCards[(string) $s->user_id] ?? 0,
                    ])
                    ->sortByDesc('month_cards')
                    ->take(self::NETWORK_LIST_LIMIT)
                    ->values()
                    ->all(),
            ],

            // Cascada: la agencia cobra Y − X sobre cada venta de sus vendedores.
            // Y = 0 → agencia "de paso" (no cobra; sus vendedores topan con la
            // comercializadora — ver CommissionHierarchyGuard).
            'hierarchy' => [
                'chain' => array_values(array_filter([
                    $commerce ? ['level' => 'commerce', 'name' => $commerce->name, 'rate' => $isAdminView ? (float) $commerce->commission : null, 'status' => $commerce->status, 'is_self' => false] : null,
                    ['level' => 'subsidiary', 'name' => $subsidiary->name, 'rate' => $rate, 'status' => $subsidiary->status, 'is_self' => true],
                ])),
                'pass_through' => $rate <= 0.001,
                'children_rate_range' => $sellers->isEmpty() ? null : [
                    'min' => (float) $sellers->min('commission'),
                    'max' => (float) $sellers->max('commission'),
                ],
            ],

            'performance' => $this->insights->performance($lotScope, 'subsidiary_commission_amount'),
            'alerts' => $this->alerts($subsidiary, $sellers, $isAdminView),
            'security' => $this->insights->security((string) $subsidiary->user_id),
            'wallets' => $this->enrichment->wallets($subsidiary->user),
            'recent_movements' => $this->enrichment->recentMovements($subsidiary->user),
            'account_meta' => $this->enrichment->accountMeta((string) $subsidiary->user_id),
        ];
    }

    /**
     * Solo estos 2 campos son autogestionables — comisión/comercio padre/
     * moneda/país nunca llegan aquí (array_intersect_key es defensa en
     * profundidad, aunque el Request ya filtre los campos aceptados).
     * admin/superadmin pueden editar vía $targetUserId igual que showOwn().
     *
     * @param array{name?: string, phone?: string} $data
     */
    public function updateOwn(array $data, ?string $targetUserId = null) : void
    {
        $subsidiary = $this->ownSubsidiaryOrFail($targetUserId);
        $subsidiary->update(array_intersect_key($data, array_flip(['name', 'phone'])));
    }

    private function alerts(Subsidiary $subsidiary, $sellers, bool $isAdminView = false) : array
    {
        $alerts = [];
        $rate = (float) $subsidiary->commission;
        $commerce = $subsidiary->commerce;

        if ($subsidiary->status !== 'ACTIVE') {
            $alerts[] = Insights::alert(
                'danger',
                'fa-ban',
                'Agencia no activa',
                'Tu agencia está ' . strtolower($subsidiary->status) . ': tus vendedores no pueden vender. Contacta a tu comercializadora.',
            );
        }

        if (! $commerce) {
            $alerts[] = Insights::alert(
                'danger',
                'fa-link-slash',
                'Sin comercializadora',
                'Tu agencia no pertenece a ninguna comercializadora: tus vendedores no pueden vender. Contacta a soporte.',
            );
        } elseif ($commerce->status !== 'ACTIVE') {
            $alerts[] = Insights::alert(
                'danger',
                'fa-store-slash',
                'Comercializadora no activa',
                "{$commerce->name} está " . strtolower($commerce->status) . ': ni tú ni tus vendedores pueden vender mientras tanto.',
            );
        }

        if ($commerce && $rate > (float) $commerce->commission + 0.001) {
            $alerts[] = Insights::alert(
                'warning',
                'fa-scale-unbalanced',
                'Comisión por encima de tu comercializadora',
                // El % de la comercializadora solo se muestra a admin.
                sprintf(
                    'Tu comisión (%s%%) supera la de %s%s. Pide a tu comercializadora que la ajuste.',
                    $this->pct($rate),
                    $commerce->name,
                    $isAdminView ? ' (' . $this->pct((float) $commerce->commission) . '%)' : '',
                ),
            );
        }

        if ($rate > 0.001) {
            $above = $sellers->filter(fn ($s) => (float) $s->commission > $rate + 0.001)->count();
            if ($above > 0) {
                $alerts[] = Insights::alert(
                    'warning',
                    'fa-scale-unbalanced',
                    'Vendedores por encima de tu comisión',
                    sprintf('%d vendedor(es) tienen comisión mayor a tu %s%%: no recibes margen sobre sus ventas.', $above, $this->pct($rate)),
                    'Revisar vendedores',
                    '/sellers',
                );
            }
        } else {
            $alerts[] = Insights::alert(
                'info',
                'fa-route',
                'Agencia sin comisión propia',
                'Tu agencia es "de paso": no cobras margen sobre las ventas de tus vendedores; la diferencia la cobra tu comercializadora.',
            );
        }

        if ($sellers->isEmpty()) {
            $alerts[] = Insights::alert(
                'info',
                'fa-user-plus',
                'Aún no tienes vendedores',
                'Crea tu primer vendedor (taquilla) para empezar a vender.',
                'Crear vendedor',
                '/sellers',
            );
        }

        return $alerts;
    }

    private function ownSubsidiaryOrFail(?string $targetUserId = null) : Subsidiary
    {
        $actorId = $this->userContext->resolveUserId();
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        $subsidiary = Subsidiary::where('user_id', $access['owner_user_id'])->first();

        abort_if(! $subsidiary, 404, 'No se encontró una agencia asociada a este usuario.');

        return $subsidiary;
    }

    private function pct(float $rate) : string
    {
        return rtrim(rtrim(number_format($rate, 2), '0'), '.');
    }
}

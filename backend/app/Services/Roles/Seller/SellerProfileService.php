<?php

namespace App\Services\Roles\Seller;

use App\Models\Roles\Seller;
use App\Services\Roles\AdminProfileAccessService;
use App\Services\Roles\CommissionHierarchyGuard;
use App\Services\Roles\RoleProfileEnrichmentService;
use App\Services\Roles\RoleProfileInsightsService as Insights;
use App\Services\Security\UserContextService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Autogestión de la ficha propia de un Seller (taquilla/vendedor) —
 * distinto de ManagerSellerService (gestión por su Commerce/Subsidiary,
 * protegido por sellers.*). Por defecto SIEMPRE resuelve el registro Seller
 * del usuario autenticado; admin/superadmin pueden pasar $targetUserId
 * (resuelto por AdminProfileAccessService) para inspeccionar la ficha de un
 * vendedor específico — sin esto, "Mi Perfil" en el sidebar era un 404
 * garantizado para admin/superadmin (no tienen registro Seller propio).
 */
final class SellerProfileService
{
    public function __construct(
        private readonly UserContextService $userContext,
        private readonly AdminProfileAccessService $adminAccess,
        private readonly RoleProfileEnrichmentService $enrichment,
        private readonly Insights $insights,
        private readonly CommissionHierarchyGuard $commissionGuard,
    ) {}

    private function ownSellerOrFail(?string $targetUserId = null): Seller
    {
        $actorId = $this->userContext->resolveUserId();
        $access = $this->adminAccess->resolve($actorId, $targetUserId);

        $seller = Seller::where('user_id', $access['owner_user_id'])->first();

        abort_if(!$seller, 404, 'No se encontró un vendedor asociado a este usuario.');

        return $seller;
    }

    public function showOwn(?string $targetUserId = null): array
    {
        $seller = $this->ownSellerOrFail($targetUserId);
        $seller->load([
            'country:id,name',
            'user:id,username,email',
            'subsidiary:id,name,status,commission,commerce_id',
            'subsidiary.commerce:id,user_id,name,status,commission',
            'managerCommerce:id,user_id,name,status,commission',
        ]);

        $commerce   = $seller->resolveCommerce();
        $subsidiary = $seller->subsidiary_id ? $seller->subsidiary : null;
        $rate       = (float) $seller->commission;

        // Cada nivel ve las comisiones SUYAS y de quienes tiene debajo, nunca
        // las de arriba: el vendedor ve la cadena (nombres y estado) pero no el
        // % de su agencia/comercializadora — ni su tope, que es justamente la
        // comisión de la agencia (CommissionHierarchyGuard::sellerCap()).
        // Admin/superadmin viendo el perfil sí ven todo.
        $isAdminView = (bool) $this->adminAccess->resolve($this->userContext->resolveUserId(), $targetUserId)['is_admin_view'];
        $upperRate   = fn (float $value): ?float => $isAdminView ? $value : null;

        $lotScope = fn (Builder $q) => $q->where('seller_user_id', $seller->user_id);

        return [
            'role'         => 'seller',
            'id'           => $seller->hashed_id,
            'name'         => $seller->name,
            'phone'        => $seller->phone,
            'status'       => $seller->status,
            'status_badge' => $seller->status_badge,
            'country'      => $seller->country?->name,
            'reports_to'   => [
                'name' => $seller->manager_name,
                'type' => $seller->subsidiary_id ? 'subsidiary' : 'commerce',
                'commerce_name' => $commerce?->name,
            ],
            'user'         => ['username' => $seller->user->username, 'email' => $seller->user->email],
            'commission'   => $rate,

            // Cascada: el vendedor cobra X; su tope es la agencia (si cobra
            // comisión propia) o la comercializadora (ver CommissionHierarchyGuard).
            'hierarchy' => [
                'chain' => array_values(array_filter([
                    $commerce ? ['level' => 'commerce', 'name' => $commerce->name, 'rate' => $upperRate((float) $commerce->commission), 'status' => $commerce->status, 'is_self' => false] : null,
                    $subsidiary ? ['level' => 'subsidiary', 'name' => $subsidiary->name, 'rate' => $upperRate((float) $subsidiary->commission), 'status' => $subsidiary->status, 'is_self' => false] : null,
                    ['level' => 'seller', 'name' => $seller->name, 'rate' => $rate, 'status' => $seller->status, 'is_self' => true],
                ])),
                'cap' => $isAdminView ? $this->commissionGuard->sellerCap($seller->subsidiary_id, $seller->manager_user_id) : null,
            ],

            'currencies' => $seller->currenciesActive()->pluck('iso_code')->all(),
            'games'      => $seller->gamesActive()->pluck('name')->all(),

            'performance'      => $this->insights->performance($lotScope, 'commission_amount'),
            'alerts'           => $this->alerts($seller, $commerce, $isAdminView),
            'security'         => $this->insights->security((string) $seller->user_id),
            'wallets'          => $this->enrichment->wallets($seller->user),
            'recent_movements' => $this->enrichment->recentMovements($seller->user),
            'account_meta'     => $this->enrichment->accountMeta((string) $seller->user_id),
        ];
    }

    /**
     * Solo estos 2 campos son autogestionables — comisión/manager/agencia
     * nunca llegan aquí (array_intersect_key es defensa en profundidad,
     * aunque el Request ya filtre los campos aceptados). admin/superadmin
     * pueden editar vía $targetUserId igual que showOwn().
     *
     * @param array{name?: string, phone?: string} $data
     */
    public function updateOwn(array $data, ?string $targetUserId = null): void
    {
        $seller = $this->ownSellerOrFail($targetUserId);
        $seller->update(array_intersect_key($data, array_flip(['name', 'phone'])));
    }

    private function alerts(Seller $seller, $commerce, bool $isAdminView = false): array
    {
        $alerts = [];

        if (!$commerce) {
            $alerts[] = Insights::alert('danger', 'fa-link-slash', 'Sin comercializadora asignada',
                'Tu taquilla no está asignada a ninguna comercializadora, así que no puedes vender. Contacta a soporte para que te reasignen.');
        } elseif (!$seller->hasActiveHierarchy()) {
            $who = $seller->status !== 'ACTIVE'
                ? 'Tu taquilla está ' . strtolower($seller->status)
                : ($seller->subsidiary && $seller->subsidiary->status !== 'ACTIVE'
                    ? "Tu agencia ({$seller->subsidiary->name}) no está activa"
                    : "Tu comercializadora ({$commerce->name}) no está activa");

            $alerts[] = Insights::alert('danger', 'fa-ban', 'No puedes vender en este momento',
                "{$who}. Las ventas quedan bloqueadas hasta que se reactive.");
        }

        $cap = $this->commissionGuard->sellerCap($seller->subsidiary_id, $seller->manager_user_id);
        if ($cap !== null && (float) $seller->commission > $cap + 0.001) {
            // El % del tope es la comisión del nivel superior: solo se muestra
            // a admin; el vendedor ve el aviso sin ese número.
            $alerts[] = Insights::alert('warning', 'fa-scale-unbalanced', 'Comisión fuera de tope',
                sprintf('Tu comisión (%s%%) supera el tope de tu nivel superior%s. Seguirás cobrando tu comisión; tu responsable debe ajustarla.',
                    rtrim(rtrim(number_format((float) $seller->commission, 2), '0'), '.'),
                    $isAdminView ? ' (' . rtrim(rtrim(number_format($cap, 2), '0'), '.') . '%)' : ''));
        }

        return $alerts;
    }
}

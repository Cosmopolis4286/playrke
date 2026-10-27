<?php

namespace App\Policies\Marketplace;

use App\Domains\Marketplace\Escrow\Models\Escrow;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para moderación de escrows del marketplace.
 * Solo superadmin/admin (permiso marketplace.manage, ver PermissionSeeder).
 *
 * resolve() es un método propio (no update()/delete() heredado de
 * BaseRolePolicy): resolver una disputa no es una mutación genérica del
 * modelo, es una acción de dominio con su propia regla — mismo criterio
 * documentado en memoria del proyecto sobre acciones que necesitan su
 * propio método explícito en vez de reusar update() de BaseRolePolicy.
 */
class EscrowPolicy extends BaseRolePolicy
{
    public function resolve(User $user, Escrow $escrow) : bool
    {
        return $this->hasAccess($user) && $escrow->status === Escrow::STATUS_DISPUTED;
    }

    protected function requiredRole() : string|array
    {
        return ['superadmin', 'admin'];
    }
}

<?php

namespace App\Policies\Commissions;

use App\Models\User;

/**
 * "Mis comisiones": cada usuario ve SOLO las suyas (vendedor, agencia,
 * comercializadora, creador de salas). Exige el permiso commissions.view-own
 * y un rol de negocio que genere comisiones — superadmin tiene todos los
 * permisos pero no tiene comisiones propias, por eso no basta el permiso.
 */
class CommissionLedgerPolicy
{
    public const BUSINESS_ROLES = ['seller', 'subsidiary', 'commerce', 'customer'];

    public function viewOwn(User $user): bool
    {
        return $user->checkPermissionTo('commissions.view-own') && $user->hasRole(self::BUSINESS_ROLES);
    }
}

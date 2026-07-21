<?php

namespace App\Policies\Roles\Seller\Manager;

use App\Models\Roles\Seller;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para el dominio Seller Manager.
 *
 * Reglas:
 * - Solo usuarios con roles permitidos pueden operar
 * - Vendedores en estados DELETED o BLOCKED no pueden mutar
 * - Compatible con authorizeResource()
 */
class ManagerSellerPolicy extends BaseRolePolicy
{
    /**
     * Roles requeridos para operar sobre vendedores.
     *
     * @return string|string[]
     */
    protected function requiredRole(): string|array
    {
        return ['superadmin', 'admin', 'commerce', 'customer'];
    }

    /**
     * Estados que bloquean operaciones mutables.
     *
     * @return string[]
     */
    protected function forbiddenStatuses(): array
    {
        return ['DELETED', 'BLOCKED'];
    }

    /**
     * Alternar estado del vendedor.
     */
    public function toggleStatus(User $user, Seller $seller): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($seller);
    }

    /**
     * Cambiar contraseña asociada al vendedor.
     */
    public function resetPassword(User $user, Seller $seller): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($seller);
    }
}

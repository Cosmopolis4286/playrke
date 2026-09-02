<?php

namespace App\Policies\Roles\Admin;

use App\Models\Roles\Subsidiary;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para el dominio Subsidiary.
 *
 * Reglas:
 * - Solo usuarios con rol admin pueden operar
 * - Sucursales en estados DELETED o BLOCKED no pueden mutar
 * - Compatible con authorizeResource()
 */
class AdminSubsidiaryPolicy extends BaseRolePolicy
{
    /**
     * Rol requerido para operar sobre sucursales.
     */
    protected function requiredRole(): string
    {
        return 'admin';
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
     * Cambiar estado de la sucursal.
     *
     * Acción de dominio (no CRUD estándar).
     *
     * @param User $user
     * @param Subsidiary $subsidiary
     * @return bool
     */
    public function changeStatus(User $user, Subsidiary $subsidiary): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($subsidiary);
    }

    /**
     * Cambiar contraseña asociada a la sucursal.
     *
     * Acción de dominio (no CRUD estándar).
     *
     * @param User $user
     * @param Subsidiary $subsidiary
     * @return bool
     */
    public function resetPassword(User $user, Subsidiary $subsidiary): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($subsidiary);
    }
}

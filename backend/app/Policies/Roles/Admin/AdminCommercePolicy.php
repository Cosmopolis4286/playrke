<?php

namespace App\Policies\Roles\Admin;

use App\Models\Roles\Commerce;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para el dominio Commerce.
 *
 * Reglas:
 * - Solo usuarios con rol admin pueden operar
 * - Comercios en estados DELETED o BLOCKED no pueden mutar
 * - Compatible con authorizeResource()
 */
class AdminCommercePolicy extends BaseRolePolicy
{
    /**
     * Cambiar estado del comercio.
     *
     * Acción de dominio (no CRUD estándar).
     */
    public function changeStatus(User $user, Commerce $commerce) : bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($commerce);
    }

    /**
     * Cambiar contraseña asociada al comercio.
     *
     * Acción de dominio (no CRUD estándar).
     */
    public function resetPassword(User $user, Commerce $commerce) : bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($commerce);
    }

    /**
     * Estados que bloquean operaciones mutables.
     *
     * @return string[]
     */
    protected function forbiddenStatuses() : array
    {
        return ['DELETED', 'BLOCKED'];
    }

    /**
     * Rol requerido para operar sobre comercios.
     */
    protected function requiredRole() : string
    {
        return 'admin';
    }
}

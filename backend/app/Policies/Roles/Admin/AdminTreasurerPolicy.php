<?php

namespace App\Policies\Roles\Admin;

use App\Models\Roles\Treasurer;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para el dominio Treasurer.
 *
 * Reglas:
 * - Solo usuarios con rol admin pueden operar
 * - Tesoreros en estados DELETED o BLOCKED no pueden mutar
 * - Compatible con authorizeResource()
 */
class AdminTreasurerPolicy extends BaseRolePolicy
{
    /**
     * Cambiar estado del tesorero.
     *
     * Acción de dominio (no CRUD estándar).
     */
    public function changeStatus(User $user, Treasurer $treasurer) : bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($treasurer);
    }

    /**
     * Cambiar contraseña asociada al tesorero.
     *
     * Acción de dominio (no CRUD estándar).
     */
    public function resetPassword(User $user, Treasurer $treasurer) : bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($treasurer);
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
     * Rol requerido para operar sobre tesoreros.
     */
    protected function requiredRole() : string
    {
        return 'admin';
    }
}

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
 * - Comercios en estados DELETED o BLOCKED no pueden mutar
 * - Compatible con authorizeResource()
 */
class AdminTreasurerPolicy extends BaseRolePolicy
{
    /**
     * Rol requerido para operar sobre comercios.
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
     * Cambiar estado del comercio.
     *
     * Acción de dominio (no CRUD estándar).
     *
     * @param User $user
     * @param Treasurer $commerce
     * @return bool
     */
    public function changeStatus(User $user, Treasurer $commerce): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($commerce);
    }

    /**
     * Cambiar contraseña asociada al comercio.
     *
     * Acción de dominio (no CRUD estándar).
     *
     * @param User $user
     * @param Treasurer $commerce
     * @return bool
     */
    public function resetPassword(User $user, Treasurer $commerce): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($commerce);
    }
}

<?php

namespace App\Policies\Users\Roles\Commerce;

use App\Models\Users\Roles\Commerce\Commerce;
use App\Models\Users\User;
use App\Policies\Users\Roles\BaseRolePolicy;

/**
 * Policy de autorización para el dominio Commerce.
 *
 * Reglas:
 * - Solo usuarios con rol admin pueden operar
 * - Comercios en estados DELETED o BLOCKED no pueden mutar
 * - Compatible con authorizeResource()
 */
class CommercePolicy extends BaseRolePolicy
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
     * @param Commerce $commerce
     * @return bool
     */
    public function changeStatus(User $user, Commerce $commerce): bool
    {
        return $this->hasAccess($user)
            && $this->canOperate($commerce);
    }

    /**
     * Cambiar contraseña asociada al comercio.
     *
     * Acción de dominio (no CRUD estándar).
     *
     * @param User $user
     * @param Commerce $commerce
     * @return bool
     */
    public function changePassword(User $user, Commerce $commerce): bool
    {
        return $this->hasAccess($user)
            && $this->canOperate($commerce);
    }
}

<?php

namespace App\Policies\Prizes;

use App\Models\Roles\Commerce;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para el dominio Prizes.
 *
 * Reglas:
 * - Solo usuarios con rol admin pueden operar
 * - Comercios en estados DELETED o BLOCKED no pueden mutar
 * - Compatible con authorizeResource()
 */
class PrizePolicy extends BaseRolePolicy
{
    /**
     * Rol requerido para operar sobre premios.
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
            && $this->canMutate($commerce);
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
    public function resetPassword(User $user, Commerce $commerce): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($commerce);
    }
}

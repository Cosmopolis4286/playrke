<?php

namespace App\Policies\Prizes;

use App\Models\Prize;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para el dominio Prizes.
 *
 * Reglas:
 * - Solo usuarios con rol admin pueden operar
 * - Compatible con authorizeResource()
 */
class PrizePolicy extends BaseRolePolicy
{
    /**
     * Rol requerido para operar sobre premios.
     *
     * @return string|string[]
     */
    protected function requiredRole(): string|array
    {
        return ['admin', 'commerce', 'customer'];
    }

    /**
     * Estados que bloquean operaciones mutables.
     *
     * @return string[]
     */
    protected function forbiddenStatuses(): array
    {
        return [];
    }

    /**
     * Cambiar estado del premio (ACTIVE / INACTIVE).
     */
    public function toggleStatus(User $user, Prize $prize): bool
    {
        return $this->hasAccess($user);
    }
}

<?php

namespace App\Policies\Roles\Admin;

use App\Models\Roles\Customer;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para el dominio Customer.
 *
 * Reglas:
 * - Solo usuarios con rol admin pueden operar
 * - Comercios en estados DELETED o BLOCKED no pueden mutar
 * - Compatible con authorizeResource()
 */
class AdminCustomerPolicy extends BaseRolePolicy
{
    /**
     * Cambiar contraseña asociada al comercio.
     *
     * Acción de dominio (no CRUD estándar).
     */
    public function resetPassword(User $user, Customer $commerce) : bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($commerce);
    }

    /**
     * Cambiar estado del comercio.
     *
     * Acción de dominio (no CRUD estándar) — antes se llamaba changeStatus(),
     * un nombre que no coincidía con AdminCustomerController::toggleStatus()
     * y por lo tanto nunca se invocaba (ni este método, ni resetPassword() ni
     * generateKey(), que no tenían ningún $this->authorize() en el
     * controller): un admin podía cambiar estado/contraseña/key de un
     * Customer sin pasar por canMutate(), es decir, incluso si estaba
     * DELETED/BLOCKED.
     */
    public function toggleStatus(User $user, Customer $commerce) : bool
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

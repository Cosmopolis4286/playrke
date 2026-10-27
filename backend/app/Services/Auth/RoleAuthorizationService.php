<?php

namespace App\Services\Auth;

use App\Models\User;

/**
 * Punto único de verificación de roles y permisos.
 *
 * Fuente de verdad: tabla `role_user` (relación User::roles()) para roles,
 * y el catálogo estándar de Spatie (`role_has_permissions`) para permisos
 * heredados por rol. Ambos se resuelven a través de esta clase para que
 * middleware, gates y policies compartan exactamente la misma regla.
 */
class RoleAuthorizationService
{
    public const SUPERADMIN_PREFIX = 'superadmin';

    /**
     * Verifica si el usuario tiene alguno de los permisos indicados.
     *
     * El superadmin siempre pasa, sin necesidad de permisos explícitos.
     * Los demás usuarios se validan contra los permisos heredados de sus roles
     * (tabla `role_has_permissions` de Spatie).
     *
     * @param string|string[] $permissions
     */
    public function hasAnyPermission(User $user, string|array $permissions) : bool
    {
        if ($this->isSuperadmin($user)) {
            return true;
        }

        return $user->hasAnyPermission(...(array) $permissions);
    }

    /**
     * Verifica si el usuario tiene alguno de los roles indicados (por prefijo o nombre).
     *
     * El superadmin siempre pasa, igual que en hasAnyPermission(), para que un
     * usuario con únicamente el rol superadmin (sin el rol admin adicional) no
     * quede bloqueado por policies/middlewares que listan roles explícitos.
     *
     * @param string|string[] $roles
     */
    public function hasAnyRole(User $user, string|array $roles) : bool
    {
        if ($this->isSuperadmin($user)) {
            return true;
        }

        return $user->hasRole($roles);
    }

    /**
     * Verifica si el usuario es superadmin (bypass total de permisos).
     */
    public function isSuperadmin(User $user) : bool
    {
        return $user->hasRole(self::SUPERADMIN_PREFIX);
    }
}

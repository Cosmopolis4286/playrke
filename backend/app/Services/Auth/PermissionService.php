<?php

namespace App\Services\Auth;

use App\Models\Users\User;
use App\Repositories\Users\Roles\RoleUserRepository;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio centralizado para validar permisos de usuario.
 *
 * Permite verificar permisos por:
 *  - roles (prefijo del rol)
 *  - prefix (alias semántico de roles)
 *  - callback personalizado
 *
 * Fuente de verdad: tabla role_user (roles activos)
 */
class PermissionService
{
    /**
     * Obtiene el usuario autenticado de forma segura.
     *
     * @return User|null
     */
    protected static function user(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Verifica si el usuario actual tiene alguno de los roles especificados.
     *
     * @param string|string[] $roles Prefijos de rol (admin, commerce, etc)
     * @return bool
     */
    public static function checkRole(string|array $roles): bool
    {
        $user = self::user();

        if (!$user) {
            return false;
        }

        /** @var RoleUserRepository $roleUserRepository */
        $roleUserRepository = app(RoleUserRepository::class);

        return $roleUserRepository->userHasAnyRole(
            $user,
            (array) $roles
        );
    }

    /**
     * Alias semántico de checkRole().
     *
     * Se mantiene para compatibilidad conceptual cuando
     * se habla de prefijos de rol.
     *
     * @param string|string[] $prefixes
     * @return bool
     */
    public static function checkPrefix(string|array $prefixes): bool
    {
        return self::checkRole($prefixes);
    }

    /**
     * Verifica permisos personalizados a través de un callback.
     *
     * @param callable(User):bool $callback
     * @return bool
     */
    public static function checkCustom(callable $callback): bool
    {
        $user = self::user();

        return $user
            ? (bool) $callback($user)
            : false;
    }

    /**
     * Verificación unificada de permisos.
     *
     * Todas las condiciones deben cumplirse.
     *
     * @param array{
     *     roles?: string|string[],
     *     prefix?: string|string[],
     *     custom?: callable(User):bool
     * } $options
     * @return bool
     */
    public static function authorize(array $options): bool
    {
        dd(self::checkRole($options['roles']));
        if (
            isset($options['roles']) &&
            ! self::checkRole($options['roles'])
        ) {
            return false;
        }

        if (
            isset($options['prefix']) &&
            ! self::checkPrefix($options['prefix'])
        ) {
            return false;
        }

        if (
            isset($options['custom']) &&
            ! self::checkCustom($options['custom'])
        ) {
            return false;
        }

        return true;
    }
}

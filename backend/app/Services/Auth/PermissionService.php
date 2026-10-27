<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * API estática de conveniencia para validar permisos del usuario autenticado
 * desde contextos sin inyección de dependencias (p.ej. FormRequest::authorize()).
 *
 * Delega toda la verificación en RoleAuthorizationService (fuente de verdad única).
 */
class PermissionService
{
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
     */
    public static function authorize(array $options) : bool
    {
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

        return ! (
            isset($options['custom']) &&
            ! self::checkCustom($options['custom'])
        );
    }

    /**
     * Verifica permisos personalizados a través de un callback.
     *
     * @param callable(User):bool $callback
     */
    public static function checkCustom(callable $callback) : bool
    {
        $user = self::user();

        return $user
            ? (bool) $callback($user)
            : false;
    }

    /**
     * Alias semántico de checkRole().
     *
     * Se mantiene para compatibilidad conceptual cuando
     * se habla de prefijos de rol.
     *
     * @param string|string[] $prefixes
     */
    public static function checkPrefix(string|array $prefixes) : bool
    {
        return self::checkRole($prefixes);
    }

    /**
     * Verifica si el usuario actual tiene alguno de los roles especificados.
     *
     * @param string|string[] $roles Prefijos de rol (admin, commerce, etc)
     */
    public static function checkRole(string|array $roles) : bool
    {
        $user = self::user();

        if (! $user) {
            return false;
        }

        return app(RoleAuthorizationService::class)->hasAnyRole($user, $roles);
    }

    /**
     * Obtiene el usuario autenticado de forma segura.
     */
    protected static function user() : ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}

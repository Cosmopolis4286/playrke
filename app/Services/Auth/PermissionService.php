<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Repositories\User\UserTypeRepository;

/**
 * Servicio centralizado para validar permisos de usuario.
 *
 * Permite verificar permisos por:
 *  - user_type (int o array)
 *  - role (string o array)
 *  - prefix (string o array desde UserType)
 *  - callback personalizado
 */
class PermissionService
{
    /**
     * Verifica si el usuario actual tiene uno de los tipos de usuario permitidos.
     *
     * @param int|int[] $requiredUserType
     * @return bool
     */
    public static function checkUserType(int|array $requiredUserType): bool
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return false;
        }

        return is_array($requiredUserType)
            ? in_array($user->user_type, $requiredUserType, true)
            : $user->user_type === $requiredUserType;
    }

    /**
     * Verifica si el usuario actual tiene alguno de los roles especificados.
     *
     * @param string|string[] $roles
     * @return bool
     */
    public static function checkRole(string|array $roles): bool
    {
        $user = Auth::user();
        if (!$user instanceof User || !property_exists($user, 'role')) {
            return false;
        }

        return is_array($roles)
            ? in_array($user->role, $roles, true)
            : $user->role === $roles;
    }

    /**
     * Verifica si el usuario actual pertenece a un UserType con el prefijo especificado.
     *
     * @param string|string[] $prefixes
     * @return bool
     */
    public static function checkPrefix(string|array $prefixes): bool
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return false;
        }

        $userPrefix = UserTypeRepository::getValueById($user->user_type, 'prefix');

        return is_array($prefixes)
            ? in_array($userPrefix, $prefixes, true)
            : $userPrefix === $prefixes;
    }

    /**
     * Verifica permisos personalizados a través de un callback.
     *
     * @param callable(User):bool $callback
     * @return bool
     */
    public static function checkCustom(callable $callback): bool
    {
        $user = Auth::user();
        return $user instanceof User ? (bool) $callback($user) : false;
    }

    /**
     * Verificación unificada: por tipo, rol, prefix o callback.
     *
     * @param array{
     *     userType?: int|int[],
     *     roles?: string|string[],
     *     prefix?: string|string[],
     *     custom?: callable(User):bool
     * } $options
     * @return bool
     */
    public static function authorize(array $options): bool
    {
        if (isset($options['userType']) && !self::checkUserType($options['userType'])) {
            return false;
        }

        if (isset($options['roles']) && !self::checkRole($options['roles'])) {
            return false;
        }

        if (isset($options['prefix']) && !self::checkPrefix($options['prefix'])) {
            return false;
        }

        if (isset($options['custom']) && !self::checkCustom($options['custom'])) {
            return false;
        }

        return true;
    }
}

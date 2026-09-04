<?php

namespace App\Services\Roles;

use App\Contracts\Roles\RoleUserRepositoryInterface;
use App\Exceptions\Domain\Roles\RoleResolutionException;

/**
 * Servicio de dominio encargado de resolver el rol activo de un usuario.
 */
final class RoleUserService
{
    public function __construct(
        private readonly RoleUserRepositoryInterface $roleUserRepository,
    ) {}

    /**
     * Obtiene el prefijo del rol activo del usuario.
     *
     * El prefijo se utiliza normalmente para:
     *  - Namespacing de permisos
     *  - Ruteo por contexto
     *  - Resolución de entidades por rol
     *
     * @param int $userId Identificador del usuario.
     *
     * @return string Prefijo del rol activo en minúsculas.
     *
     * @throws RoleResolutionException Si el usuario no tiene un rol activo válido
     *                         o el rol no define un prefijo.
     */
    public function getActiveUserRolePrefix(int $userId): string
    {
        $role = $this->roleUserRepository
            ->getActiveByUser($userId)
            ->first(fn($roleUser) => !empty($roleUser->role?->prefix))
            ?->role
            ?->prefix;

        if (!$role) {
            throw RoleResolutionException::invalidOrUnidentified();
        }

        return strtolower($role);
    }
}

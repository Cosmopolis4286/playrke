<?php

namespace App\Services\Roles;

use App\Contracts\Roles\RoleUserRepositoryInterface;

class RoleUserService
{
    public function __construct(
        protected RoleUserRepositoryInterface $repository,
    ) {}

    /**
     * Resuelve el prefijo del tipo de usuario.
     *
     * @param int $userId
     * @return string|null
     */
    /**
     * Resuelve el prefijo del tipo de usuario basándose en roles activos.
     */
    public function getActiveUserRolePrefix(int $userId): ?string
    {
        // Obtener roles activos asignados al usuario
        $activeRoles = $this->repository->getActiveByUser($userId);

        foreach ($activeRoles as $roleUser) {
            $role = $roleUser->role;
            if ($role && $role->prefix) {
                // Retornamos el primer prefijo encontrado (ajustar lógica si hay más de uno)
                return strtolower($role->prefix);
            }
        }

        return null;
    }
}

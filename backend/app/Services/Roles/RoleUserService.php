<?php

namespace App\Services\Roles;

use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerRepositoryInterface;
use App\Contracts\Roles\RoleUserRepositoryInterface;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Request;

class RoleUserService
{
    public function __construct(
        protected RoleUserRepositoryInterface $roleUserRepository,
        protected CommerceRepositoryInterface $commerceRepository,
        protected CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * Resuelve el prefijo del tipo de usuario basándose en roles activos.
     *
     * @param int $userId
     * @return string
     *
     * @throws \DomainException Si no se encuentra un rol válido
     */
    public function getActiveUserRolePrefix(int $userId): string
    {
        $activeRoles = $this->roleUserRepository->getActiveByUser($userId);

        foreach ($activeRoles as $roleUser) {
            $prefix = $roleUser->role->prefix ?? null;
            if ($prefix) {
                return strtolower($prefix);
            }
        }

        throw new \DomainException('Rol inválido o no identificado.');
    }

    /**
     * Resuelve el contexto del usuario que ejecuta la operación.
     *
     * Prioridad:
     * 1. Usuario autenticado (Auth)
     * 2. Usuario público identificado por token
     *
     * @param string|null $token Token público opcional.
     *
     * @return array{
     *   id: int,
     *   authenticated: bool
     * }
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function resolveUser(?string $token = null): array
    {
        if (Auth::check()) {
            return [
                'id' => Auth::id(),
                'authenticated' => true,
            ];
        }

        if ($token) {
            // Aquí deberías resolver el userId real a partir del token
            // Ej: $userId = $this->tokenResolver->resolve($token);

            $userId = (int) $token; // placeholder válido mientras tanto

            return [
                'id' => $userId,
                'authenticated' => false,
            ];
        }

        abort(404, 'Usuario no identificado.');
    }

    /**
     * Obtiene el ID asociado al usuario según su rol activo o un rol especificado.
     *
     * Si no se proporciona un rol, se obtiene el rol activo del usuario automáticamente.
     * Soporta roles 'commerce' y 'customer'.
     *
     * @param int $userId ID del usuario
     * @param string|null $role Prefijo del rol opcional ('commerce', 'customer')
     *
     * @return int ID asociado al usuario según el rol
     *
     * @throws \DomainException Si el rol es inválido o no soportado
     */
    public function getUserRoleEntityId(int $userId, ?string $role): int
    {
        if (!$role) {
            $role = $this->getActiveUserRolePrefix($userId);
        }

        return match ($role) {
            'commerce' => $this->commerceRepository->getValueByUserId($userId, 'id'),
            'customer' => $this->customerRepository->getValueByUserId($userId, 'id'),
            default => throw new DomainException("Rol inválido: {$role}"),
        };
    }
}

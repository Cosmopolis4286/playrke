<?php

namespace App\Services\Roles;

use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerRepositoryInterface;
use App\Contracts\Roles\RoleUserRepositoryInterface;
use DomainException;

final class RoleUserService
{
    public function __construct(
        private readonly RoleUserRepositoryInterface $roleUserRepository,
        private readonly CommerceRepositoryInterface $commerceRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {}

    /**
     * Obtiene el prefijo del rol activo del usuario.
     *
     * @param int $userId
     * @return string
     *
     * @throws DomainException
     */
    public function getActiveUserRolePrefix(int $userId): string
    {
        $role = $this->roleUserRepository
            ->getActiveByUser($userId)
            ->first(fn ($roleUser) => !empty($roleUser->role?->prefix))
            ?->role
            ?->prefix;

        if (!$role) {
            throw new DomainException('Rol inválido o no identificado.');
        }

        return strtolower($role);
    }

    /**
     * Resuelve el usuario que ejecuta la acción.
     *
     * ⚠️ Este método NO depende de Auth ni HTTP.
     * El caller debe pasar explícitamente el contexto.
     *
     * @param int|null $authenticatedUserId
     * @param string|null $publicToken
     *
     * @return array{id:int, authenticated:bool}
     *
     * @throws DomainException
     */
    public function resolveUser(
        ?int $authenticatedUserId = null,
        ?string $publicToken = null
    ): array {
        if ($authenticatedUserId) {
            return [
                'id' => $authenticatedUserId,
                'authenticated' => true,
            ];
        }

        if ($publicToken) {
            return [
                'id' => $this->resolveUserIdFromToken($publicToken),
                'authenticated' => false,
            ];
        }

        throw new DomainException('Usuario no identificado.');
    }

    /**
     * Obtiene el ID de la entidad asociada al usuario según su rol.
     *
     * @param int $userId
     * @param string|null $role
     *
     * @return int
     *
     * @throws DomainException
     */
    public function getUserRoleEntityId(int $userId, ?string $role = null): int
    {
        $role ??= $this->getActiveUserRolePrefix($userId);

        return match ($role) {
            'commerce' => $this->commerceRepository->getValueByUserId($userId, 'id'),
            'customer' => $this->customerRepository->getValueByUserId($userId, 'id'),
            default => throw new DomainException("Rol inválido: {$role}"),
        };
    }

    /**
     * Resuelve el userId real desde un token público.
     *
     * @param string $token
     * @return int
     *
     * @throws DomainException
     */
    private function resolveUserIdFromToken(string $token): int
    {
        // Placeholder → aquí luego entra TokenResolver / ApiKey / JWT, etc.
        if (!is_numeric($token)) {
            throw new DomainException('Token inválido.');
        }

        return (int) $token;
    }
}

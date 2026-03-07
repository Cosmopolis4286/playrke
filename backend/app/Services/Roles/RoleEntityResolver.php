<?php

namespace App\Services\Roles;

use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerRepositoryInterface;
use App\Contracts\Roles\Seller\SellerRepositoryInterface;
use App\Services\Security\UserContextService;
use DomainException;

/**
 * Servicio de dominio encargado de resolver el identificador
 * de la entidad asociada a un usuario según su rol.
 *
 * Ejemplos:
 *  - Rol "commerce"  → entidad Commerce
 *  - Rol "customer"  → entidad Customer
 *  - Rol "seller"    → entidad Seller
 */
final class RoleEntityResolver
{
    public function __construct(
        private readonly CommerceRepositoryInterface $commerceRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly SellerRepositoryInterface $sellerRepository,
        private readonly UserContextService $userContext,
        private readonly RoleUserService $roleUserService,
    ) {}

    /**
     * Resuelve el ID de la entidad asociada a un usuario según su rol.
     *
     * @param int|null    $userId Identificador del usuario.
     * @param string|null $role   Rol del usuario (ej: "commerce", "customer", "seller").
     *
     * @return string ID de la entidad asociada al rol.
     *
     * @throws DomainException Si el rol no es válido o no está soportado.
     */
    public function resolveEntityId(?int $userId = null, ?string $role = null): string
    {
        if (!$userId) {
            $userId = $this->userContext->resolveUserId();
        }

        if (!$role) {
            $role = $this->roleUserService->getActiveUserRolePrefix($userId);
        }

        return match ($role) {
            'commerce' => $this->commerceRepository->getValueByUserId($userId, 'id'),
            'customer' => $this->customerRepository->getValueByUserId($userId, 'id'),
            'seller'   => $this->sellerRepository->getValueByUserId($userId, 'id'),
            default    => throw new DomainException("Rol inválido: {$role}"),
        };
    }
}

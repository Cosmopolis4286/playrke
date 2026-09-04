<?php

namespace App\Services\Roles;

use App\Contracts\Roles\Admin\AdminRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerRepositoryInterface;
use App\Contracts\Roles\Seller\SellerRepositoryInterface;
use App\Contracts\Roles\Subsidiary\SubsidiaryRepositoryInterface;
use App\Exceptions\Domain\Roles\RoleResolutionException;
use App\Services\Security\UserContextService;

/**
 * Servicio de dominio encargado de resolver el identificador
 * de la entidad asociada a un usuario según su rol.
 *
 * Ejemplos:
 *  - Rol "admin"      → entidad Admin
 *  - Rol "commerce"   → entidad Commerce
 *  - Rol "subsidiary" → entidad Subsidiary (agencia)
 *  - Rol "customer"   → entidad Customer
 *  - Rol "seller"     → entidad Seller
 */
final class RoleEntityResolver
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
        private readonly CommerceRepositoryInterface $commerceRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly SellerRepositoryInterface $sellerRepository,
        private readonly SubsidiaryRepositoryInterface $subsidiaryRepository,
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
     * @throws RoleResolutionException Si el rol no es válido o no está soportado.
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
            'admin', 'superadmin' => $this->adminRepository->getValueByUserId($userId, 'id'),
            'commerce'            => $this->commerceRepository->getValueByUserId($userId, 'id'),
            'subsidiary'          => $this->subsidiaryRepository->getValueByUserId($userId, 'id'),
            'customer'            => $this->customerRepository->getValueByUserId($userId, 'id'),
            'seller'              => $this->sellerRepository->getValueByUserId($userId, 'id'),
            default               => throw RoleResolutionException::unsupportedRole($role),
        };
    }
}

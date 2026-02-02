<?php

namespace App\Services\Roles;

use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerRepositoryInterface;
use DomainException;

/**
 * Servicio de dominio encargado de resolver el identificador
 * de la entidad asociada a un usuario según su rol.
 *
 * Ejemplos:
 *  - Rol "commerce"  → entidad Commerce
 *  - Rol "customer"  → entidad Customer
 */
final class RoleEntityResolver
{
    public function __construct(
        private readonly CommerceRepositoryInterface $commerceRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {}

    /**
     * Resuelve el ID de la entidad asociada a un usuario según su rol.
     *
     * @param int    $userId Identificador del usuario.
     * @param string $role   Rol del usuario (ej: "commerce", "customer").
     *
     * @return int ID de la entidad asociada al rol.
     *
     * @throws DomainException Si el rol no es válido o no está soportado.
     */
    public function resolveEntityId(int $userId, string $role): int
    {
        return match ($role) {
            'commerce' => $this->commerceRepository->getValueByUserId($userId, 'id'),
            'customer' => $this->customerRepository->getValueByUserId($userId, 'id'),
            default => throw new DomainException("Rol inválido: {$role}"),
        };
    }
}

<?php

namespace App\Services\Roles\Admin\Commerce;

use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\DTOs\Roles\Admin\Commerce\AdminCommerceDTO;
use App\Services\Admin\Commerce\AdminCommerceCrudService;

/**
 * Servicio de aplicación para exponer
 * los casos de uso del dominio AdminCommerce.
 *
 * Responsabilidades:
 * - Resolver contexto de usuario
 * - Delegar operaciones a servicios especializados
 * - Mantener controllers desacoplados
 *
 * NO contiene lógica de dominio ni infraestructura.
 */
final class AdminCommerceService
{
    public function __construct(
        private readonly AdminCommerceCrudService $crud,
        private readonly CommerceRepositoryInterface $commerceRepository,
    ) {}

    /**
     * Obtener todas los comercios.
     *
     * @return object Listado de comercios
     */
    public function getAll(): object
    {
        return $this->commerceRepository->all();
    }

    /**
     * Crear una tasa de cambio.
     *
     * @param AdminCommerceDTO $dto DTO con los datos de la tasa de cambio
     * @return bool True si la creación fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la creación
     */
    public function create(AdminCommerceDTO $dto): bool
    {
        $userId = $this->resolveUserId($dto->tokenId);
        return $this->crud->create($dto, $userId);
    }

    /**
     * Obtener una tasa de cambio para visualización.
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     * @return AdminCommerceResource Recurso para visualización
     *
     * @throws DomainException Si no se puede resolver el usuario o no existe la tasa
     */
    public function show(string $exchangeRateId): AdminCommerceResource
    {
        $userId = $this->resolveUserId();
        return $this->crud->show($exchangeRateId, $userId);
    }

    /**
     * Actualizar una tasa de cambio.
     *
     * @param AdminCommerceDTO $dto DTO con datos actualizados
     * @return bool True si la actualización fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la actualización
     */
    public function update(AdminCommerceDTO $dto): bool
    {
        $userId = $this->resolveUserId($dto->tokenId);
        return $this->crud->update($dto, $userId);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una tasa de cambio.
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     * @return bool True si el cambio fue exitoso
     *
     * @throws DomainException Si no se puede resolver el usuario o falla el cambio
     */
    public function toggleStatus(string $exchangeRateId): bool
    {
        $userId = $this->resolveUserId();
        return $this->crud->changeStatusByRole($exchangeRateId, $userId);
    }

    /**
     * Eliminar una tasa de cambio (soft delete).
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     * @return bool True si la eliminación fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la eliminación
     */
    public function delete(string $exchangeRateId): bool
    {
        $userId = $this->resolveUserId();
        return $this->crud->deleteByRole($exchangeRateId, $userId);
    }

    /**
     * Retorna las tasas crudas obtenidas de la API externa para mostrar en confirmación.
     *
     * @param string|null $publicToken Token público opcional para resolver usuario
     * @return array|null Retorna un arreglo con las tasas si la llamada fue exitosa,
     *                    o null si ocurrió algún error.
     */
    public function getRawRates(?string $publicToken = null): ?array
    {
        $userId = $this->resolveUserId($publicToken);
        return $this->api->fetch();
    }
}

<?php

namespace App\Services\Roles\Admin\Commerce;

use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\DTOs\Roles\Admin\Commerce\AdminCommerceDTO;
use App\Http\Resources\Roles\Admin\Commerce\AdminCommerceResource;
use App\Http\Services\User\UserPasswordService;
use App\Models\Roles\Commerce;
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
        private readonly UserPasswordService $userPasswordService
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
     * @throws DomainException Si falla la creación
     */
    public function create(AdminCommerceDTO $dto): bool
    {
        return $this->crud->create($dto, $userId);
    }

    /**
     * Obtener una tasa de cambio para visualización.
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     * @return AdminCommerceResource Recurso para visualización
     *
     * @throws DomainException Si no existe la tasa
     */
    public function show(string $exchangeRateId): AdminCommerceResource
    {
        return $this->crud->show($exchangeRateId);
    }

    /**
     * Actualizar una tasa de cambio.
     *
     * @param AdminCommerceDTO $dto DTO con datos actualizados
     * @return bool True si la actualización fue exitosa
     *
     * @throws DomainException Si falla la actualización
     */
    public function update(AdminCommerceDTO $dto): bool
    {
        return $this->crud->update($dto);
    }

    /**
     * Eliminar una tasa de cambio (soft delete).
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     * @return bool True si la eliminación fue exitosa
     *
     * @throws DomainException Si falla la eliminación
     */
    public function delete(string $exchangeRateId): bool
    {
        return $this->crud->delete($exchangeRateId);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una tasa de cambio.
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     * @return bool True si el cambio fue exitoso
     *
     * @throws DomainException Si falla el cambio
     */
    public function toggleStatus(string $exchangeRateId): bool
    {
        return $this->crud->changeStatusByRole($exchangeRateId);
    }

    public function resetPassword(Commerce $commerce)
    {
        return $this->userPasswordService->resetPassword($commerce);
    }

    public function destroy(Commerce $commerce)
    {
        return $this->crud->destroy($commerce);
    }
}

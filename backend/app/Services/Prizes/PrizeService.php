<?php

namespace App\Services\Prizes;

use App\DTOs\Roles\Admin\Commerce\AdminCommerceDTO;
use App\Http\Resources\Roles\Admin\Commerce\AdminCommerceResource;
use App\Models\Roles\Commerce;
use App\Services\User\UserPasswordService;
use App\Exceptions\Domain\Roles\Admin\Commerce\AdminCommerceException;

/**
 * Servicio de aplicación para exponer
 * los casos de uso del dominio AdminCommerce.
 *
 * Responsabilidades:
 * - Orquestar casos de uso
 * - Delegar operaciones a servicios de dominio
 * - Mantener controllers desacoplados
 *
 * NO contiene lógica de dominio ni infraestructura.
 */
final class PrizeService
{
    public function __construct(
        private readonly \App\Services\Roles\Admin\Commerce\AdminCommerceContextService $contex,
        private readonly \App\Services\Roles\Admin\Commerce\AdminCommerceCrudService $crud,
        private readonly UserPasswordService $userPasswordService
    ) {}

    /**
     * Obtener todos los comercios.
     *
     * @return array
     */
    public function fetchList(): array
    {
        return $this->contex->resolveCommerces()['commerces']['rows'] ?? [];
    }

    /**
     * Crear un comercio.
     *
     * @param AdminCommerceDTO $dto DTO con los datos del comercio
     * @return string Contraseña generada para el comercio
     *
     * @throws AdminCommerceException Si falla la creación
     */
    public function create(AdminCommerceDTO $dto): string
    {
        return $this->crud->create($dto);
    }

    /**
     * Obtener un comercio para visualización.
     *
     * @param Commerce $commerce Model
     * @return AdminCommerceResource Recurso para visualización
     *
     * @throws AdminCommerceException Si el comercio no es válido o no existe
     */
    public function show(Commerce $commerce): AdminCommerceResource
    {
        return $this->crud->show($commerce);
    }

    /**
     * Actualizar un comercio.
     *
     * @param Commerce $commerce Model
     * @param AdminCommerceDTO $dto DTO con datos actualizados
     *
     * @throws AdminCommerceException Si falla la actualización
     */
    public function update(Commerce $commerce, AdminCommerceDTO $dto): void
    {
        $this->crud->update($commerce, $dto);
    }

    /**
     * Eliminar un comercio (soft delete).
     *
     * @param Commerce $commerce Model
     *
     * @throws AdminCommerceException Si falla la eliminación
     */
    public function delete(Commerce $commerce): void
    {
        $this->crud->delete($commerce);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del comercio.
     *
     * @param Commerce $commerce Model
     *
     * @throws AdminCommerceException Si falla el cambio de estado
     */
    public function toggleStatus(Commerce $commerce): void
    {
        $this->crud->toggleStatus($commerce);
    }

    /**
     * Eliminar un comercio permanentemente (hard delete).
     *
     * @param Commerce $commerce Model
     *
     * @throws AdminCommerceException Si falla la eliminación definitiva
     */
    public function destroy(Commerce $commerce): void
    {
        $this->crud->destroy($commerce);
    }
}

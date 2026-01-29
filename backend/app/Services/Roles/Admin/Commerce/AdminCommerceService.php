<?php

namespace App\Services\Roles\Admin\Commerce;

use App\DTOs\Roles\Admin\Commerce\AdminCommerceDTO;
use App\Http\Resources\Roles\Admin\Commerce\AdminCommerceResource;
use App\Models\Roles\Commerce;
use App\Services\User\UserPasswordService;
use App\Services\Roles\Admin\Commerce\AdminCommerceCrudService;
use App\Exceptions\Domain\Roles\Admin\Commerce\AdminCommerceException;
use App\Services\Roles\Admin\AdminService;
use Illuminate\Database\Eloquent\Collection;

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
final class AdminCommerceService
{
    public function __construct(
        private readonly AdminService $adminService,
        private readonly AdminCommerceCrudService $crud,
        private readonly UserPasswordService $userPasswordService
    ) {}

    /**
     * Obtener todos los comercios.
     *
     * @return Collection<int, Commerce>
     */
    public function getAll(): Collection
    {
        return $this->crud->getAll();
    }

    /**
     * Listado de comercios
     *
     * @return array Listado para pasar a la vista Inertia.
     */
    public function listFiltered(): array
    {
        return $this->adminService->context('commerces')['commerces'];
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
     * Resetear la contraseña del comercio.
     *
     * @param Commerce $commerce Model
     * @return string Nueva contraseña generada
     *
     * @throws AdminCommerceException Si el comercio no está activo o falla el reseteo
     */
    public function resetPassword(Commerce $commerce): string
    {
        if ($commerce->status !== 'ACTIVE') {
            throw AdminCommerceException::passwordResetRequiresActiveStatus();
        }

        return $this->userPasswordService->resetPassword($commerce);
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

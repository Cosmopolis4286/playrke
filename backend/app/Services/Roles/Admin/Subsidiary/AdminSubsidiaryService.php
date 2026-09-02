<?php

namespace App\Services\Roles\Admin\Subsidiary;

use App\DTOs\Roles\Admin\Subsidiary\AdminSubsidiaryDTO;
use App\Http\Resources\Roles\Admin\Subsidiary\AdminSubsidiaryResource;
use App\Models\Roles\Subsidiary;
use App\Services\User\UserPasswordService;
use App\Exceptions\Domain\Roles\Admin\Subsidiary\AdminSubsidiaryException;

/**
 * Servicio de aplicación para exponer
 * los casos de uso del dominio AdminSubsidiary.
 *
 * Responsabilidades:
 * - Orquestar casos de uso
 * - Delegar operaciones a servicios de dominio
 * - Mantener controllers desacoplados
 *
 * NO contiene lógica de dominio ni infraestructura.
 */
final class AdminSubsidiaryService
{
    public function __construct(
        private readonly AdminSubsidiaryContextService $contex,
        private readonly AdminSubsidiaryCrudService $crud,
        private readonly UserPasswordService $userPasswordService
    ) {}

    /**
     * Obtener todas las sucursales.
     *
     * @return array
     */
    public function fetchList(): array
    {
        return $this->contex->resolveSubsidiaries()['subsidiaries']['rows'] ?? [];
    }

    /**
     * Crear una sucursal.
     *
     * @param AdminSubsidiaryDTO $dto DTO con los datos de la sucursal
     * @return string Contraseña generada para la sucursal
     *
     * @throws AdminSubsidiaryException Si falla la creación
     */
    public function create(AdminSubsidiaryDTO $dto): string
    {
        return $this->crud->create($dto);
    }

    /**
     * Obtener una sucursal para visualización.
     *
     * @param Subsidiary $subsidiary Model
     * @return AdminSubsidiaryResource Recurso para visualización
     *
     * @throws AdminSubsidiaryException Si la sucursal no es válida o no existe
     */
    public function show(Subsidiary $subsidiary): AdminSubsidiaryResource
    {
        return $this->crud->show($subsidiary);
    }

    /**
     * Actualizar una sucursal.
     *
     * @param Subsidiary $subsidiary Model
     * @param AdminSubsidiaryDTO $dto DTO con datos actualizados
     *
     * @throws AdminSubsidiaryException Si falla la actualización
     */
    public function update(Subsidiary $subsidiary, AdminSubsidiaryDTO $dto): void
    {
        $this->crud->update($subsidiary, $dto);
    }

    /**
     * Eliminar una sucursal (soft delete).
     *
     * @param Subsidiary $subsidiary Model
     *
     * @throws AdminSubsidiaryException Si falla la eliminación
     */
    public function delete(Subsidiary $subsidiary): void
    {
        $this->crud->delete($subsidiary);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de la sucursal.
     *
     * @param Subsidiary $subsidiary Model
     *
     * @throws AdminSubsidiaryException Si falla el cambio de estado
     */
    public function toggleStatus(Subsidiary $subsidiary): void
    {
        $this->crud->toggleStatus($subsidiary);
    }

    /**
     * Resetear la contraseña de la sucursal.
     *
     * @param Subsidiary $subsidiary Model
     * @return string Nueva contraseña generada
     *
     * @throws AdminSubsidiaryException Si la sucursal no está activa o falla el reseteo
     */
    public function resetPassword(Subsidiary $subsidiary): string
    {
        if ($subsidiary->status !== 'ACTIVE') {
            throw AdminSubsidiaryException::passwordResetRequiresActiveStatus();
        }

        return $this->userPasswordService->resetPassword($subsidiary);
    }

    /**
     * Eliminar una sucursal permanentemente (hard delete).
     *
     * @param Subsidiary $subsidiary Model
     *
     * @throws AdminSubsidiaryException Si falla la eliminación definitiva
     */
    public function destroy(Subsidiary $subsidiary): void
    {
        $this->crud->destroy($subsidiary);
    }
}

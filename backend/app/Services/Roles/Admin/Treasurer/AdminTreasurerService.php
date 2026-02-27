<?php

namespace App\Services\Roles\Admin\Treasurer;

use App\DTOs\Roles\Admin\Treasurer\AdminTreasurerDTO;
use App\Http\Resources\Roles\Admin\Treasurer\AdminTreasurerResource;
use App\Models\Roles\Treasurer;
use App\Services\User\UserPasswordService;
use App\Exceptions\Domain\Roles\Admin\Treasurer\AdminTreasurerException;

/**
 * Servicio de aplicación para exponer
 * los casos de uso del dominio AdminTreasurer.
 *
 * Responsabilidades:
 * - Orquestar casos de uso
 * - Delegar operaciones a servicios de dominio
 * - Mantener controllers desacoplados
 *
 * NO contiene lógica de dominio ni infraestructura.
 */
final class AdminTreasurerService
{
    public function __construct(
        private readonly \App\Services\Roles\Admin\Treasurer\AdminTreasurerContextService $contex,
        private readonly \App\Services\Roles\Admin\Treasurer\AdminTreasurerCrudService $crud,
        private readonly UserPasswordService $userPasswordService
    ) {}

    /**
     * Obtener todos los tesoreros.
     *
     * @return array
     */
    public function fetchList(): array
    {
        return $this->contex->resolveTreasurers()['treasurers']['rows'] ?? [];
    }

    /**
     * Crear un tesorero.
     *
     * @param AdminTreasurerDTO $dto DTO con los datos del tesorero
     * @return string Contraseña generada para el tesorero
     *
     * @throws AdminTreasurerException Si falla la creación
     */
    public function create(AdminTreasurerDTO $dto): string
    {
        return $this->crud->create($dto);
    }

    /**
     * Obtener un tesorero para visualización.
     *
     * @param Treasurer $treasurer Model
     * @return AdminTreasurerResource Recurso para visualización
     *
     * @throws AdminTreasurerException Si el tesorero no es válido o no existe
     */
    public function show(Treasurer $treasurer): AdminTreasurerResource
    {
        return $this->crud->show($treasurer);
    }

    /**
     * Actualizar un tesorero.
     *
     * @param Treasurer $treasurer Model
     * @param AdminTreasurerDTO $dto DTO con datos actualizados
     *
     * @throws AdminTreasurerException Si falla la actualización
     */
    public function update(Treasurer $treasurer, AdminTreasurerDTO $dto): void
    {
        $this->crud->update($treasurer, $dto);
    }

    /**
     * Eliminar un tesorero (soft delete).
     *
     * @param Treasurer $treasurer Model
     *
     * @throws AdminTreasurerException Si falla la eliminación
     */
    public function delete(Treasurer $treasurer): void
    {
        $this->crud->delete($treasurer);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del tesorero.
     *
     * @param Treasurer $treasurer Model
     *
     * @throws AdminTreasurerException Si falla el cambio de estado
     */
    public function toggleStatus(Treasurer $treasurer): void
    {
        $this->crud->toggleStatus($treasurer);
    }

    /**
     * Resetear la contraseña del tesorero.
     *
     * @param Treasurer $treasurer Model
     * @return string Nueva contraseña generada
     *
     * @throws AdminTreasurerException Si el tesorero no está activo o falla el reseteo
     */
    public function resetPassword(Treasurer $treasurer): string
    {
        if ($treasurer->status !== 'ACTIVE') {
            throw AdminTreasurerException::passwordResetRequiresActiveStatus();
        }

        return $this->userPasswordService->resetPassword($treasurer);
    }

    /**
     * Eliminar un tesorero permanentemente (hard delete).
     *
     * @param Treasurer $treasurer Model
     *
     * @throws AdminTreasurerException Si falla la eliminación definitiva
     */
    public function destroy(Treasurer $treasurer): void
    {
        $this->crud->destroy($treasurer);
    }
}

<?php

namespace App\Services\Roles\Seller\Manager;

use App\DTOs\Roles\Seller\Manager\ManagerSellerDTO;
use App\Http\Resources\Roles\Seller\Manager\ManagerSellerResource;
use App\Models\Roles\Commerce;
use App\Services\User\UserPasswordService;
use App\Services\Roles\Seller\Manager\ManagerSellerCrudService;
use App\Exceptions\Domain\Roles\Seller\Manager\ManagerSellerException;
use App\Services\Security\UserContextService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio de aplicación para exponer
 * los casos de uso del dominio ManagerSeller.
 *
 * Responsabilidades:
 * - Orquestar casos de uso
 * - Delegar operaciones a servicios de dominio
 * - Mantener controllers desacoplados
 *
 * NO contiene lógica de dominio ni infraestructura.
 */
final class ManagerSellerService
{
    public function __construct(
        private readonly ManagerSellerCrudService $crud,
        private readonly UserContextService $userContext,
        private readonly UserPasswordService $userPasswordService,

    ) {}

    /**
     * Obtener contexto de vendedores del usuario manager.
     *
     * @param string|null $publicToken Token público de API
     * @return array Contexto de vendedores para la vista Inertia
     */
    public function context(?string $publicToken = null): array
    {
        // Resolver ID del usuario
        $userId = $this->userContext->resolveUserId($publicToken);
        dd($this->crud->context($userId));
        return $this->crud->context($userId);
    }

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
        return $this->crud->context()['sellers'];
    }

    /**
     * Crear un comercio.
     *
     * @param ManagerSellerDTO $dto DTO con los datos del comercio
     * @return string Contraseña generada para el comercio
     *
     * @throws ManagerSellerException Si falla la creación
     */
    public function create(ManagerSellerDTO $dto): string
    {
        return $this->crud->create($dto);
    }

    /**
     * Obtener un comercio para visualización.
     *
     * @param Commerce $commerce Model
     * @return ManagerSellerResource Recurso para visualización
     *
     * @throws ManagerSellerException Si el comercio no es válido o no existe
     */
    public function show(Commerce $commerce): ManagerSellerResource
    {
        return $this->crud->show($commerce);
    }

    /**
     * Actualizar un comercio.
     *
     * @param Commerce $commerce Model
     * @param ManagerSellerDTO $dto DTO con datos actualizados
     *
     * @throws ManagerSellerException Si falla la actualización
     */
    public function update(Commerce $commerce, ManagerSellerDTO $dto): void
    {
        $this->crud->update($commerce, $dto);
    }

    /**
     * Eliminar un comercio (soft delete).
     *
     * @param Commerce $commerce Model
     *
     * @throws ManagerSellerException Si falla la eliminación
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
     * @throws ManagerSellerException Si falla el cambio de estado
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
     * @throws ManagerSellerException Si el comercio no está activo o falla el reseteo
     */
    public function resetPassword(Commerce $commerce): string
    {
        if ($commerce->status !== 'ACTIVE') {
            throw ManagerSellerException::passwordResetRequiresActiveStatus();
        }

        return $this->userPasswordService->resetPassword($commerce);
    }

    /**
     * Eliminar un comercio permanentemente (hard delete).
     *
     * @param Commerce $commerce Model
     *
     * @throws ManagerSellerException Si falla la eliminación definitiva
     */
    public function destroy(Commerce $commerce): void
    {
        $this->crud->destroy($commerce);
    }
}

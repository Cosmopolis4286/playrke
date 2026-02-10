<?php

namespace App\Services\Roles\Seller\Manager;

use App\DTOs\Roles\Seller\Manager\ManagerSellerDTO;
use App\Http\Resources\Roles\Seller\Manager\ManagerSellerResource;
use App\Models\Roles\Seller;
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
        return $this->crud->context($userId)->toView();
    }

    /**
     * Obtener todos los vendedores.
     *
     * @param string|null $publicToken Token público de API
     * @return Collection<int, Seller>
     */
    public function listFiltered(?string $publicToken = null): Collection
    {
        // Resolver ID del usuario
        $userId = $this->userContext->resolveUserId($publicToken);
        return $this->crud->getAll($userId);
    }

    /**
     * Crear un vendedor.
     *
     * @param ManagerSellerDTO $dto DTO con los datos del vendedor
     * @param string|null $publicToken Token público de API
     * @return string Contraseña generada para el vendedor
     *
     * @throws ManagerSellerException Si falla la creación
     */
    public function create(ManagerSellerDTO $dto, ?string $publicToken = null): string
    {
        // Resolver ID del usuario
        $managerUserId = $this->userContext->resolveUserId($publicToken);
        return $this->crud->create($dto, $managerUserId);
    }

    /**
     * Obtener un vendedor para visualización.
     *
     * @param Seller $seller Model
     * @return ManagerSellerResource Recurso para visualización
     *
     * @throws ManagerSellerException Si el vendedor no es válido o no existe
     */
    public function show(Seller $seller): ManagerSellerResource
    {
        return $this->crud->show($seller);
    }

    /**
     * Actualizar un vendedor.
     *
     * @param Seller $seller Model
     * @param ManagerSellerDTO $dto DTO con datos actualizados
     *
     * @throws ManagerSellerException Si falla la actualización
     */
    public function update(Seller $seller, ManagerSellerDTO $dto): void
    {
        $this->crud->update($seller, $dto);
    }

    /**
     * Eliminar un vendedor (soft delete).
     *
     * @param Seller $seller Model
     *
     * @throws ManagerSellerException Si falla la eliminación
     */
    public function delete(Seller $seller): void
    {
        $this->crud->delete($seller);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del vendedor.
     *
     * @param Seller $seller Model
     *
     * @throws ManagerSellerException Si falla el cambio de estado
     */
    public function toggleStatus(Seller $seller): void
    {
        $this->crud->toggleStatus($seller);
    }

    /**
     * Resetear la contraseña del vendedor.
     *
     * @param Seller $seller Model
     * @return string Nueva contraseña generada
     *
     * @throws ManagerSellerException Si el vendedor no está activo o falla el reseteo
     */
    public function resetPassword(Seller $seller): string
    {
        if ($seller->status !== 'ACTIVE') {
            throw ManagerSellerException::passwordResetRequiresActiveStatus();
        }

        return $this->userPasswordService->resetPassword($seller);
    }

    /**
     * Eliminar un vendedor permanentemente (hard delete).
     *
     * @param Seller $seller Model
     *
     * @throws ManagerSellerException Si falla la eliminación definitiva
     */
    public function destroy(Seller $seller): void
    {
        $this->crud->destroy($seller);
    }
}

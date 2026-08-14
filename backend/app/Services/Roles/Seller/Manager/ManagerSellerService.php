<?php

namespace App\Services\Roles\Seller\Manager;

use App\Contracts\Roles\Subsidiary\SubsidiaryRepositoryInterface;
use App\DTOs\Roles\Seller\Manager\ManagerSellerDTO;
use App\Http\Resources\Roles\Seller\Manager\ManagerSellerResource;
use App\Models\Roles\Seller;
use App\Services\Roles\RoleUserService;
use App\Services\User\UserPasswordService;
use App\Services\Roles\Seller\Manager\ManagerSellerCrudService;
use App\Exceptions\Domain\Roles\Seller\Manager\ManagerSellerException;
use App\Services\Security\UserContextService;

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
        private readonly ManagerSellerContextService $contex,
        private readonly ManagerSellerCrudService $crud,
        private readonly UserContextService $userContext,
        private readonly UserPasswordService $userPasswordService,
        private readonly RoleUserService $roleUserService,
        private readonly SubsidiaryRepositoryInterface $subsidiaryRepository,
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
        return $this->contex->resolve($userId)->toView();
    }

    /**
     * Obtener todos los vendedores.
     *
     * @param string|null $publicToken Token público de API
     * @return array Listado de vendedores para la vista Inertia
     */
    public function fetchList(?string $publicToken = null): array
    {
        // Resolver ID del usuario
        $userId = $this->userContext->resolveUserId($publicToken);
        return $this->contex->getAll($userId);
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
        // Si el DTO trae manager_user_id (caso admin eligiendo explícitamente
        // a quién asignar el seller — puede ser una Comercializadora o una
        // Agencia, ver ManagerSellerForm.vue), resolverlo igual que el resto:
        // el manager puede ser cualquier rol de la jerarquía comercial.
        // Si no, resolver desde el usuario autenticado (caso
        // commerce/subsidiary/customer creando sus propios sellers).
        $targetUserId = $dto->managerUserId !== null
            ? (int) $dto->managerUserId
            : $this->userContext->resolveUserId($publicToken);

        [$managerUserId, $subsidiaryId] = $this->resolveManagerForUser($targetUserId);

        return $this->crud->create($dto, $managerUserId, $subsidiaryId);
    }

    /**
     * Resuelve el manager_user_id real (siempre el user_id de un Commerce) y
     * el subsidiary_id (si aplica) para un usuario dado, sin importar si ese
     * usuario es 'commerce' o 'subsidiary' (agencia).
     *
     * manager_user_id se persiste NOT NULL con FK a users, y todo el código
     * que resuelve el commerce de un seller (managerCommerce(), reportes,
     * BingoCardGenerateService) asume que apunta al user_id de un Commerce
     * — nunca al de una Subsidiary directamente. Cuando el manager real es
     * una agencia, se guarda el Commerce padre en manager_user_id y la
     * agencia en subsidiary_id (ver Seller::resolveCommerce()).
     *
     * @return array{0: int, 1: ?string} [managerUserId, subsidiaryId]
     */
    private function resolveManagerForUser(int $targetUserId): array
    {
        $role = $this->roleUserService->getActiveUserRolePrefix($targetUserId);

        if ($role !== 'subsidiary') {
            return [$targetUserId, null];
        }

        $subsidiary = $this->subsidiaryRepository->getByUserId($targetUserId, ['id', 'commerce_id']);

        if (!$subsidiary || !$subsidiary->commerce_id) {
            throw ManagerSellerException::creationFailed();
        }

        $commerceUserId = \App\Models\Roles\Commerce::where('id', $subsidiary->commerce_id)->value('user_id');

        if (!$commerceUserId) {
            throw ManagerSellerException::creationFailed();
        }

        return [(int) $commerceUserId, $subsidiary->id];
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

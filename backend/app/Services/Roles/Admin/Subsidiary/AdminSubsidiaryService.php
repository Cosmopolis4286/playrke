<?php

namespace App\Services\Roles\Admin\Subsidiary;

use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\DTOs\Roles\Admin\Subsidiary\AdminSubsidiaryDTO;
use App\Http\Resources\Roles\Admin\Subsidiary\AdminSubsidiaryResource;
use App\Models\Roles\Subsidiary;
use App\Services\Roles\CommissionHierarchyGuard;
use App\Services\Roles\RoleUserService;
use App\Services\Security\UserContextService;
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
        private readonly UserPasswordService $userPasswordService,
        private readonly RoleUserService $roleUserService,
        private readonly UserContextService $userContext,
        private readonly CommerceRepositoryInterface $commerceRepository,
        private readonly CommissionHierarchyGuard $commissionGuard,
    ) {}

    /**
     * Resuelve el rol activo y el user_id del actor autenticado —
     * superadmin se normaliza a 'admin' (mismo criterio que
     * ManagerSellerContextService::resolve()): ambos ven el árbol completo
     * sin restricción.
     *
     * @return array{0: string, 1: int}
     */
    private function resolveActorRole(): array
    {
        $userId = $this->userContext->resolveUserId();
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);
        if ($role === 'superadmin') {
            $role = 'admin';
        }
        return [$role, $userId];
    }

    /**
     * Obtener todas las sucursales.
     *
     * @return array
     */
    public function fetchList(): array
    {
        [$role, $userId] = $this->resolveActorRole();
        return $this->contex->resolveSubsidiaries($role, $userId)['subsidiaries']['rows'] ?? [];
    }

    /**
     * Asegura que, si el actor autenticado es 'commerce', el commerce_id
     * enviado en el DTO sea el suyo propio — sin este guard, un commerce
     * podría enviar el id de OTRO comercio en el body del form (el Request
     * solo valida que el id EXISTA, no que sea el propio) y
     * AdminSubsidiaryCrudService lo persistiría tal cual. admin/superadmin
     * no tienen restricción (gestionan el árbol completo).
     */
    private function assertOwnCommerceOrAdmin(?string $commerceId): void
    {
        if ($commerceId === null) {
            return;
        }

        [$role, $userId] = $this->resolveActorRole();
        if ($role !== 'commerce') {
            return;
        }

        $ownCommerce = $this->commerceRepository->getByUserId($userId, ['id']);

        if (!$ownCommerce || (string) $ownCommerce->id !== (string) $commerceId) {
            throw AdminSubsidiaryException::creationFailed();
        }
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
        $this->assertOwnCommerceOrAdmin($dto->commerce);

        // Tope: comisión de la agencia ≤ la de su comercializadora.
        $this->commissionGuard->assertSubsidiaryRate((float) ($dto->commission ?? 0), (string) $dto->commerce);

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
        if ($dto->commerce !== null) {
            $this->assertOwnCommerceOrAdmin($dto->commerce);
        }

        // Topes contra el padre EFECTIVO (el nuevo si se traslada) y contra
        // sus vendedores — solo si cambia la tasa o la comercializadora.
        if ($dto->commission !== null || $dto->commerce !== null) {
            $this->commissionGuard->assertSubsidiaryRate(
                (float) ($dto->commission ?? $subsidiary->commission),
                (string) ($dto->commerce ?? $subsidiary->commerce_id),
                (string) $subsidiary->id,
            );
        }

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

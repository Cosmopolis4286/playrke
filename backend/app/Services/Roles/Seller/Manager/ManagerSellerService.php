<?php

namespace App\Services\Roles\Seller\Manager;

use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Subsidiary\SubsidiaryRepositoryInterface;
use App\DTOs\Roles\Seller\Manager\ManagerSellerDTO;
use App\Http\Resources\Roles\Seller\Manager\ManagerSellerResource;
use App\Models\Roles\Seller;
use App\Services\Roles\CommissionHierarchyGuard;
use App\Services\Roles\RoleUserService;
use App\Services\User\UserPasswordService;
use App\Services\Roles\Seller\Manager\ManagerSellerCrudService;
use App\Exceptions\Domain\Roles\Seller\Manager\ManagerSellerException;
use App\Services\Security\UserContextService;
use Illuminate\Support\Facades\Auth;

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
        private readonly CommerceRepositoryInterface $commerceRepository,
        private readonly CommissionHierarchyGuard $commissionGuard,
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
        // manager_user_id explícito (ManagerSellerForm.vue): el destino se
        // valida contra el actor igual que en update() — antes se aceptaba
        // tal cual, y un commerce/subsidiary podía crear sellers dentro de
        // la red de OTRO comercio con solo enviar su user_id. Sin destino
        // explícito (o si el actor no puede elegirlo), el seller cuelga del
        // propio usuario autenticado.
        $explicit = $dto->managerUserId !== null
            ? $this->resolveExplicitManagerForActor($dto->managerUserId)
            : null;

        [$managerUserId, $subsidiaryId] = $explicit
            ?? $this->resolveManagerForUser($this->userContext->resolveUserId($publicToken));

        // Tope: comisión del vendedor ≤ la de su agencia/comercializadora.
        $this->commissionGuard->assertSellerRate((float) $dto->commission, $subsidiaryId, $managerUserId);

        return $this->crud->create($dto, $managerUserId, $subsidiaryId);
    }

    /**
     * Resuelve un manager_user_id elegido explícitamente, validando que el
     * actor autenticado pueda asignar sellers a ese destino:
     * - admin/superadmin: cualquier Commerce o Subsidiary del sistema.
     * - commerce: solo a sí mismo o a SUS PROPIAS subsidiaries.
     * - subsidiary (y cualquier otro rol): no elige destino — null, el
     *   caller decide (create: cuelga del propio actor; update: sin cambios).
     *
     * @return array{0: int, 1: ?string}|null [managerUserId, subsidiaryId]
     *
     * @throws ManagerSellerException Si el destino no pertenece al actor
     */
    private function resolveExplicitManagerForActor(string $targetUserId): ?array
    {
        $actor = Auth::user();
        $actorRole = $actor ? $this->roleUserService->getActiveUserRolePrefix((int) $actor->id) : null;

        // Mismo criterio que ManagerSellerContextService::resolve().
        if ($actorRole === 'superadmin') {
            $actorRole = 'admin';
        }

        if ($actorRole === 'admin') {
            return $this->resolveManagerForUser((int) $targetUserId);
        }

        if ($actorRole !== 'commerce') {
            return null;
        }

        [$managerUserId, $subsidiaryId] = $this->resolveManagerForUser((int) $targetUserId);

        $ownCommerce = $this->commerceRepository->getByUserId((int) $actor->id, ['id', 'user_id']);

        if (!$ownCommerce || (string) $managerUserId !== (string) $ownCommerce->user_id) {
            throw ManagerSellerException::creationFailed();
        }

        if ($subsidiaryId !== null) {
            $targetSubsidiary = $this->subsidiaryRepository->getByUserId((int) $targetUserId, ['id', 'commerce_id']);

            if (!$targetSubsidiary || (string) $targetSubsidiary->commerce_id !== (string) $ownCommerce->id) {
                throw ManagerSellerException::creationFailed();
            }
        }

        return [$managerUserId, $subsidiaryId];
    }

    /**
     * Resuelve el manager_user_id real (siempre el user_id de un Commerce) y
     * el subsidiary_id (si aplica) para un usuario dado, sin importar si ese
     * usuario es 'commerce' o 'subsidiary' (agencia).
     *
     * manager_user_id se persiste con FK a users, y todo el código que
     * resuelve el commerce de un seller (managerCommerce(), reportes,
     * BingoCardGenerateService) asume que apunta al user_id de un Commerce
     * — nunca al de una Subsidiary directamente. Cuando el manager real es
     * una agencia, se guarda el Commerce padre en manager_user_id y la
     * agencia en subsidiary_id (ver Seller::resolveCommerce()).
     *
     * Whitelist explícita de roles válidos ('commerce' reporta directo sin
     * agencia intermedia; 'subsidiary' resuelve
     * vía su Commerce padre más abajo), no blacklist — antes
     * `if ($role !== 'subsidiary') return [$targetUserId, null]` asumía
     * implícitamente que cualquier rol distinto de 'subsidiary' era válido,
     * sin comprobarlo: un $targetUserId de un seller, player o admin se
     * habría persistido igual como manager_user_id. La validación de
     * entrada para el campo explícito manager_user_id del DTO vive en
     * ManagerSellerRequest::rules() (verifica rol commerce/subsidiary
     * contra role_user), pero ese Request NO cubre el caso implícito (un
     * commerce/subsidiary/customer creando su propio seller sin pasar
     * managerUserId, ver create() arriba) — este método es la única fuente
     * de verdad del dominio para ambos casos y no debe depender
     * exclusivamente de esa capa.
     *
     * @return array{0: int, 1: ?string} [managerUserId, subsidiaryId]
     */
    private function resolveManagerForUser(int $targetUserId): array
    {
        $role = $this->roleUserService->getActiveUserRolePrefix($targetUserId);

        // 'customer' excluido: es una rama independiente sin sellers propios
        // (ver ManagerSellerPolicy::ownsSeller()). Antes se aceptaba aquí,
        // dejando sellers que el customer no podía operar (403) y que nunca
        // podían vender (hasActiveHierarchy() sin Commerce resoluble).
        if ($role === 'commerce') {
            return [$targetUserId, null];
        }

        if ($role !== 'subsidiary') {
            throw ManagerSellerException::invalidManager();
        }

        $subsidiary = $this->subsidiaryRepository->getByUserId($targetUserId, ['id', 'commerce_id']);

        if (!$subsidiary || !$subsidiary->commerce_id) {
            throw ManagerSellerException::invalidManager();
        }

        $commerceUserId = \App\Models\Roles\Commerce::where('id', $subsidiary->commerce_id)->value('user_id');

        if (!$commerceUserId) {
            throw ManagerSellerException::invalidManager();
        }

        return [(int) $commerceUserId, $subsidiary->id];
    }

    /**
     * Opciones de "Manager" para el select de reasignación de un usuario
     * 'commerce' — acotadas server-side a sí mismo + sus propias
     * subsidiaries (nunca expone comercios/agencias ajenas, a diferencia de
     * /admin/get-user-info, que lista todo el sistema y solo admin debe
     * poder consultar).
     *
     * @param string|null $publicToken Token público de API
     * @return array{self: array{user_id: string, name: string}|null, subsidiaries: array<int, array{user_id: string, name: string}>}
     */
    public function managerOptions(?string $publicToken = null): array
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        $commerce = $this->commerceRepository->getByUserId($userId, ['id', 'user_id', 'name']);

        if (!$commerce) {
            return ['self' => null, 'subsidiaries' => []];
        }

        $subsidiaries = $this->subsidiaryRepository->getByCommerce($commerce->id)
            ->load('user:id')
            ->map(fn ($subsidiary) => [
                'user_id' => (string) $subsidiary->user_id,
                'name'    => $subsidiary->name,
            ])
            ->values()
            ->all();

        return [
            'self' => ['user_id' => (string) $commerce->user_id, 'name' => $commerce->name],
            'subsidiaries' => $subsidiaries,
        ];
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
     * Reasignación de padre (Commerce/Subsidiary), si el DTO trae
     * manager_user_id (ver ManagerSellerForm.vue):
     * - admin: puede apuntar a cualquier Commerce o Subsidiary del sistema.
     * - commerce: solo puede mover SU PROPIO seller entre reportar directo a
     *   sí mismo o a cualquiera de SUS PROPIAS subsidiaries (jerarquía
     *   Commerce → Subsidiary → Seller) — nunca a otro comercio ajeno.
     * - subsidiary: no reasigna en absoluto — "una sucursal no puede mover
     *   un vendedor por encima" (no puede subir el seller a su Commerce
     *   padre ni moverlo a otra agencia). Cualquier manager_user_id que
     *   llegue de un subsidiary se ignora.
     * Ownership del seller ACTUAL ya lo valida ManagerSellerPolicy::
     * ownsSeller() vía authorizeResource() antes de llegar aquí — esto valida
     * el DESTINO de la reasignación, que la Policy no cubre.
     *
     * @param Seller $seller Model
     * @param ManagerSellerDTO $dto DTO con datos actualizados
     *
     * @throws ManagerSellerException Si falla la actualización o el destino no es válido
     */
    public function update(Seller $seller, ManagerSellerDTO $dto): void
    {
        // 'subsidiary' (y cualquier otro rol sin permiso de reasignar) →
        // null: manager_user_id/subsidiary_id del seller no cambian.
        [$managerUserId, $subsidiaryId] = $dto->managerUserId !== null
            ? ($this->resolveExplicitManagerForActor($dto->managerUserId) ?? [null, null])
            : [null, null];

        // Tope contra el padre EFECTIVO tras la edición (el nuevo si se
        // reasigna, si no el actual). Solo si la tasa o el padre CAMBIAN de
        // verdad: ManagerSellerForm reenvía siempre commission y
        // manager_user_id con los valores actuales, así que "viene en el
        // DTO" no significa "cambió" — sin esta comparación, un seller legado
        // que ya violaba el tope no se podía editar en absoluto (ni renombrar).
        $parentChanged = $managerUserId !== null && (
            (string) $managerUserId !== (string) $seller->manager_user_id
            || (string) $subsidiaryId !== (string) $seller->subsidiary_id
        );

        if ($parentChanged || CommissionHierarchyGuard::rateChanged($dto->commission, $seller->commission)) {
            $this->commissionGuard->assertSellerRate(
                (float) ($dto->commission ?? $seller->commission),
                $managerUserId !== null ? $subsidiaryId : $seller->subsidiary_id,
                $managerUserId ?? $seller->manager_user_id,
            );
        }

        $this->crud->update($seller, $dto, $managerUserId, $subsidiaryId);
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

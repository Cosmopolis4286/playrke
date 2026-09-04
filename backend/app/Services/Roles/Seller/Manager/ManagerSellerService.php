<?php

namespace App\Services\Roles\Seller\Manager;

use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Subsidiary\SubsidiaryRepositoryInterface;
use App\DTOs\Roles\Seller\Manager\ManagerSellerDTO;
use App\Http\Resources\Roles\Seller\Manager\ManagerSellerResource;
use App\Models\Roles\Seller;
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
     * manager_user_id se persiste con FK a users, y todo el código que
     * resuelve el commerce de un seller (managerCommerce(), reportes,
     * BingoCardGenerateService) asume que apunta al user_id de un Commerce
     * — nunca al de una Subsidiary directamente. Cuando el manager real es
     * una agencia, se guarda el Commerce padre en manager_user_id y la
     * agencia en subsidiary_id (ver Seller::resolveCommerce()).
     *
     * Whitelist explícita de roles válidos ('commerce'/'customer' reportan
     * directo sin agencia intermedia, mismo criterio ya usado en
     * ManagerSellerContextService::resolveSellers(); 'subsidiary' resuelve
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

        if ($role === 'commerce' || $role === 'customer') {
            return [$targetUserId, null];
        }

        if ($role !== 'subsidiary') {
            throw ManagerSellerException::creationFailed();
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
        $managerUserId = null;
        $subsidiaryId = null;

        if ($dto->managerUserId !== null) {
            $actor = Auth::user();
            $actorRole = $actor ? $this->roleUserService->getActiveUserRolePrefix((int) $actor->id) : null;
            // Mismo criterio que ManagerSellerContextService::resolve():
            // superadmin siempre tiene el mismo alcance que admin en esta
            // pantalla (reasignar cualquier seller a cualquier árbol).
            if ($actorRole === 'superadmin') {
                $actorRole = 'admin';
            }

            if ($actorRole === 'admin') {
                [$managerUserId, $subsidiaryId] = $this->resolveManagerForUser((int) $dto->managerUserId);
            } elseif ($actorRole === 'commerce') {
                [$managerUserId, $subsidiaryId] = $this->resolveManagerForUser((int) $dto->managerUserId);

                $ownCommerce = $this->commerceRepository->getByUserId((int) $actor->id, ['id', 'user_id']);

                if (!$ownCommerce || (string) $managerUserId !== (string) $ownCommerce->user_id) {
                    throw ManagerSellerException::creationFailed();
                }

                if ($subsidiaryId !== null) {
                    $targetSubsidiary = $this->subsidiaryRepository->getByUserId((int) $dto->managerUserId, ['id', 'commerce_id']);

                    if (!$targetSubsidiary || (string) $targetSubsidiary->commerce_id !== (string) $ownCommerce->id) {
                        throw ManagerSellerException::creationFailed();
                    }
                }
            }
            // 'subsidiary' (y cualquier otro rol) — sin bloque: managerUserId
            // queda null, manager_user_id/subsidiary_id del seller no cambian.
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

<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Admin\AdminExchangeRateRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Http\Resources\ExchangeRate\ExchangeRateResource;
use App\Http\Resources\ExchangeRate\AdminExchangeRateResource;
use App\Services\Roles\RoleEntityResolver;
use App\Services\Roles\RoleUserService;
use App\Traits\ExchangeRate\ResolvesExchangeRateForeignKey;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de aplicación para operaciones CRUD de tasas de cambio.
 *
 * Responsabilidades:
 * - Resolver rol activo del usuario
 * - Resolver entidad asociada al rol
 * - Delegar operaciones a repositorios según rol
 *
 * NO maneja autenticación ni lógica HTTP.
 *
 * @final
 */
final class ExchangeRateCrudService
{
    use ResolvesExchangeRateForeignKey;

    public function __construct(
        private readonly ExchangeRateDomainService $domain,
        private readonly CommerceExchangeRateRepositoryInterface $commerce,
        private readonly CustomerExchangeRateRepositoryInterface $customer,
        private readonly AdminExchangeRateRepositoryInterface $admin,
        private readonly RoleUserService $roleUserService,
        private readonly RoleEntityResolver $roleEntityResolver,
    ) {}

    /**
     * Crear una tasa de cambio para el rol activo.
     *
     * @throws DomainException
     */
    public function create(ExchangeRateDTO $dto, int $userId): bool
    {
        [$repo, $role, $entityId] = $this->resolveContext($userId);

        // For admin role, save user_id for tracking
        if ($role === 'admin') {
            return DB::transaction(function () use ($repo, $dto, $role, $userId) {
                $repo->create($dto->toArray() + ['user_id' => $userId]);

                Log::info('ExchangeRate creado', [
                    'role'        => $role,
                    'currency_id' => $dto->currencyId,
                    'user_id'     => $userId,
                ]);

                return true;
            });
        }

        $model = $repo->getModel();
        $foreignKey = $this->foreignKeyData($entityId, $model);

        return DB::transaction(function () use ($repo, $dto, $role, $foreignKey) {
            $repo->create($dto->toArray() + $foreignKey);

            Log::info('ExchangeRate creado', [
                'role'        => $role,
                'currency_id' => $dto->currencyId,
            ]);

            return true;
        });
    }

    /**
     * Obtener una tasa de cambio para visualización.
     *
     * @throws DomainException
     */
    public function show(string $exchangeRateId, int $userId): ExchangeRateResource|AdminExchangeRateResource
    {
        [$repo, $role] = $this->resolveContext($userId);

        if ($role === 'admin') {
            $exchangeRate = $repo->findWithRelations(
                ['currency:id,iso_code,name'],
                $exchangeRateId,
                ['id', 'currency_id', 'amount', 'created_at', 'updated_at']
            );

            throw_if(
                !$exchangeRate,
                DomainException::class,
                "Tasa de cambio no encontrada: {$exchangeRateId}"
            );

            return new AdminExchangeRateResource($exchangeRate);
        }

        $exchangeRate = $repo->findWithRelations(
            ['currency:id,iso_code'],
            $exchangeRateId,
            ['id', 'currency_id', 'amount', 'status']
        );

        throw_if(
            !$exchangeRate,
            DomainException::class,
            "Tasa de cambio no encontrada: {$exchangeRateId}"
        );

        return new ExchangeRateResource($exchangeRate);
    }

    /**
     * Actualizar una tasa de cambio existente.
     *
     * @throws DomainException
     */
    public function update(ExchangeRateDTO $dto, int $userId): bool
    {
        throw_if(!$dto->id, DomainException::class, 'ID requerido');

        [$repo, $role] = $this->resolveContext($userId);

        if ($role === 'admin') {
            return DB::transaction(function () use ($repo, $dto, $userId) {
                $oldAmount = $repo->getValueById($dto->id, 'amount');

                throw_if(
                    is_null($oldAmount),
                    DomainException::class,
                    "Tasa con ID {$dto->id} no encontrada"
                );

                // For admin, update the amount and user_id
                $repo->update($dto->id, [
                    'amount'   => $dto->amount,
                    'user_id'  => $userId
                ]);

                Log::info('ExchangeRate actualizado', [
                    'id'         => $dto->id,
                    'old_amount' => (float) $oldAmount,
                    'new_amount' => $dto->amount,
                    'user_id'    => $userId,
                ]);

                return true;
            });
        }

        return DB::transaction(function () use ($repo, $dto) {
            $oldAmount = $repo->getValueById($dto->id, 'amount');

            throw_if(
                is_null($oldAmount),
                DomainException::class,
                "Tasa con ID {$dto->id} no encontrada"
            );

            // Marcar tasa anterior como OLD
            $repo->update($dto->id, ['status' => 'OLD']);

            $old = $repo->findOrFail($dto->id)->toArray();
            unset($old['id']);

            $repo->create(array_merge($old, [
                'percentage' => $this->domain->calculatePercentage($oldAmount, $dto->amount),
                'amount'     => $dto->amount,
                'status'     => $dto->status,
            ]));

            Log::info('ExchangeRate actualizado', [
                'old_id'     => $dto->id,
                'old_amount' => (float) $oldAmount,
                'new_amount' => $dto->amount,
            ]);

            return true;
        });
    }

    /**
     * Eliminar una tasa de cambio según rol.
     *
     * @throws DomainException
     */
    public function deleteByRole(string $id, int $userId): bool
    {
        [$repo] = $this->resolveContext($userId);

        throw_if(
            !$repo->exists($id),
            DomainException::class,
            "Tasa de cambio no encontrada: {$id}"
        );

        return $repo->delete($id);
    }

    /**
     * Alternar estado activo/inactivo.
     *
     * @throws DomainException
     */
    public function changeStatusByRole(string $id, int $userId): bool
    {
        [$repo, $role] = $this->resolveContext($userId);

        return $repo->toggleStatus(
            $repo->findOrFail($id)
        );
    }

    /* =====================================================
     | Resolución de contexto
     |=====================================================*/

    /**
     * Resuelve repositorio, rol y entidad asociada al usuario.
     *
     * @return array{0: mixed, 1: string, 2: int}
     *
     * @throws DomainException
     */
    private function resolveContext(int $userId): array
    {
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        throw_if(
            !$role,
            DomainException::class,
            "No se pudo resolver el rol del usuario {$userId}"
        );

        $entityId = $this->roleEntityResolver->resolveEntityId($userId, $role);

        throw_if(
            !$entityId,
            DomainException::class,
            "No se encontró entidad asociada al rol '{$role}'"
        );

        return [$this->repo($role), $role, $entityId];
    }

    /**
     * Retorna el repositorio correspondiente según el rol.
     *
     * @throws DomainException
     */
    private function repo(
        string $role
    ): CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface|AdminExchangeRateRepositoryInterface {
        return match ($role) {
            'commerce' => $this->commerce,
            'customer' => $this->customer,
            'admin' => $this->admin,
            default    => throw new DomainException("Rol inválido: {$role}"),
        };
    }
}

<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Http\Resources\ExchangeRate\ExchangeRateResource;
use App\Services\Roles\RoleUserService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de aplicación responsable de operaciones CRUD
 * sobre ExchangeRate según el rol activo del usuario.
 *
 * Orquesta:
 * - Resolución de rol
 * - Reglas de dominio
 * - Persistencia vía repositorios
 */
final class ExchangeRateCrudService
{
    public function __construct(
        private CommerceExchangeRateRepositoryInterface $commerce,
        private CustomerExchangeRateRepositoryInterface $customer,
        private RoleUserService $roleUserService,
        private ExchangeRateDomainService $domain,
    ) {}

    /**
     * Crea o restaura una tasa de cambio para el rol activo.
     *
     * @throws DomainException
     */
    public function create(ExchangeRateDTO $dto, int $userId): bool
    {
        $role = $this->resolveRole($userId);
        $roleEntityId = $this->resolveRoleEntityId($userId, $role);

        return DB::transaction(function () use ($dto, $role, $roleEntityId, $userId) {
            $this->repo($role)->createOrRestoreByCurrency($roleEntityId, $dto);

            Log::info('ExchangeRate creado/restaurado', [
                'user_id'     => $userId,
                'role'        => $role,
                'currency_id' => $dto->currencyId,
            ]);

            return true;
        });
    }

    /**
     * Obtiene una tasa de cambio para visualización.
     */
    public function show(string $exchangeRateId, int $userId): ExchangeRateResource
    {
        $exchangeRate = $this->repo(
            $this->resolveRole($userId)
        )->findWithRelations(
            ['currency:id,iso_code'],
            $exchangeRateId,
            ['id', 'currency_id', 'amount', 'status']
        );

        return new ExchangeRateResource($exchangeRate);
    }

    /**
     * Actualiza una tasa de cambio existente.
     *
     * @throws DomainException
     */
    public function update(ExchangeRateDTO $dto, int $userId): bool
    {
        throw_if(!$dto->id, DomainException::class, 'ID requerido');

        $repo = $this->repo($this->resolveRole($userId));

        return DB::transaction(function () use ($repo, $dto) {
            $oldAmount = $repo->getValueById($dto->id, 'amount');

            $repo->update($dto->id, [
                ...$dto->toArray(),
                'percentage' => $this->domain->calculatePercentage($oldAmount, $dto->amount),
                'deleted_at' => null,
            ]);

            return true;
        });
    }

    /**
     * Elimina una tasa de cambio según el rol.
     *
     * @throws DomainException
     */
    public function deleteByRole(string $id, int $userId): bool
    {
        $repo = $this->repo($this->resolveRole($userId));

        return $repo->delete($id);
    }

    /**
     * Alterna el estado de una tasa de cambio según el rol.
     *
     * @throws DomainException
     */
    public function changeStatusByRole(string $id, int $userId): bool
    {
        $repo = $this->repo($this->resolveRole($userId));

        return $repo->toggleStatus($repo->findOrFail($id));
    }

    /* =====================================================
     | Resolución de dependencias de dominio
     |=====================================================*/

    /**
     * Resuelve el rol activo del usuario.
     *
     * @throws DomainException
     */
    private function resolveRole(int $userId): string
    {
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        if (!$role) {
            throw new DomainException("No se pudo resolver el rol del usuario {$userId}");
        }

        return $role;
    }

    /**
     * Resuelve el ID de la entidad asociada al rol.
     *
     * @throws DomainException
     */
    private function resolveRoleEntityId(int $userId, string $role): int
    {
        $roleEntityId = $this->roleUserService->getUserRoleEntityId($userId, $role);

        if (!$roleEntityId) {
            throw new DomainException(
                "No se encontró entidad asociada al rol '{$role}' para el usuario {$userId}"
            );
        }

        return $roleEntityId;
    }

    /**
     * Devuelve el repositorio correspondiente al rol.
     *
     * @throws DomainException
     */
    private function repo(
        string $role
    ): CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface {
        return match ($role) {
            'commerce' => $this->commerce,
            'customer' => $this->customer,
            default    => throw new DomainException("Rol inválido: {$role}"),
        };
    }
}

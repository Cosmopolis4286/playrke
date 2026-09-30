<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Admin\AdminExchangeRateRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Exceptions\Domain\ExchangeRate\ExchangeRateException;
use App\Exceptions\Domain\Roles\RoleResolutionException;
use App\Http\Resources\ExchangeRate\ExchangeRateResource;
use App\Http\Resources\ExchangeRate\AdminExchangeRateResource;
use App\Models\ExchangeRate;
use App\Services\Roles\RoleEntityResolver;
use App\Services\Roles\RoleUserService;
use App\Traits\ExchangeRate\ResolvesExchangeRateForeignKey;
use Illuminate\Support\Facades\Cache;
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
     * @throws RoleResolutionException
     */
    public function create(ExchangeRateDTO $dto, int $userId): bool
    {
        [$repo, $role, $entityId] = $this->resolveContext($userId);

        // For admin/superadmin/treasurer role (tasa oficial), save user_id for tracking
        if (in_array($role, ['admin', 'superadmin', 'treasurer'])) {
            $result = DB::transaction(function () use ($repo, $dto, $role, $userId) {
                $repo->create($dto->toArray() + ['user_id' => $userId]);

                Log::info('ExchangeRate creado', [
                    'role'        => $role,
                    'currency_id' => $dto->currencyId,
                    'user_id'     => $userId,
                ]);

                return true;
            });

            $this->forgetActiveRateCache($dto->currencyId);

            return $result;
        }

        $model = $repo->getModel();
        $foreignKey = $this->foreignKeyData($entityId, $model);

        $result = DB::transaction(function () use ($repo, $dto, $role, $foreignKey) {
            $repo->create($dto->toArray() + $foreignKey);

            Log::info('ExchangeRate creado', [
                'role'        => $role,
                'currency_id' => $dto->currencyId,
            ]);

            return true;
        });

        $this->forgetActiveRateCache($dto->currencyId);

        return $result;
    }

    /**
     * Obtener una tasa de cambio para visualización.
     *
     * @throws ExchangeRateException
     */
    public function show(string $exchangeRateId, int $userId): ExchangeRateResource|AdminExchangeRateResource
    {
        [$repo] = $this->resolveContext($userId);

        $exchangeRate = $repo->findWithRelations(
            ['currency:id,iso_code'],
            $exchangeRateId,
            ['id', 'currency_id', 'amount', 'status']
        );

        if (!$exchangeRate) {
            throw ExchangeRateException::notFound($exchangeRateId);
        }

        return new ExchangeRateResource($exchangeRate);
    }

    /**
     * Actualizar una tasa de cambio existente.
     *
     * @throws ExchangeRateException
     */
    public function update(ExchangeRateDTO $dto, int $userId): bool
    {
        if (!$dto->id) {
            throw ExchangeRateException::idRequired();
        }

        [$repo, $role] = $this->resolveContext($userId);

        if (in_array($role, ['admin', 'superadmin', 'treasurer'])) {
            $result = DB::transaction(function () use ($repo, $dto, $userId) {
                $oldAmount = $repo->getValueById($dto->id, 'amount');

                if (is_null($oldAmount)) {
                    throw ExchangeRateException::notFoundById((string) $dto->id);
                }

                // percentage recalculado contra el amount ANTERIOR — antes
                // este update() nunca lo tocaba, así que el campo se quedaba
                // pegado en el valor de cuando la fila se creó por última
                // vez (típicamente el del seeder), sin importar cuánto
                // cambiara amount en ediciones posteriores. Mismo cálculo
                // que usa la rama commerce/customer (calculatePercentage),
                // solo que aquí no hace falta crear una fila nueva porque
                // admin/superadmin/treasurer actualizan in-place.
                $percentage = $oldAmount > 0
                    ? $this->domain->calculatePercentage((float) $oldAmount, $dto->amount)
                    : 0.0;

                // For admin/treasurer, update the amount and user_id.
                // source explícito a SOURCE_USER: sin esto, editar a mano
                // una fila que el cronjob había creado (source=system)
                // conservaba ese origen aunque ahora la haya tocado un
                // usuario real.
                $repo->update($dto->id, [
                    'amount'     => $dto->amount,
                    'percentage' => $percentage,
                    'user_id'    => $userId,
                    'source'     => ExchangeRate::SOURCE_USER,
                ]);

                Log::info('ExchangeRate actualizado', [
                    'id'         => $dto->id,
                    'old_amount' => (float) $oldAmount,
                    'new_amount' => $dto->amount,
                    'percentage' => $percentage,
                    'user_id'    => $userId,
                ]);

                return true;
            });

            $this->forgetActiveRateCache($dto->currencyId);

            return $result;
        }

        $result = DB::transaction(function () use ($repo, $dto) {
            $oldAmount = $repo->getValueById($dto->id, 'amount');

            if (is_null($oldAmount)) {
                throw ExchangeRateException::notFoundById((string) $dto->id);
            }

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

        $this->forgetActiveRateCache($dto->currencyId);

        return $result;
    }

    /**
     * Eliminar una tasa de cambio según rol.
     *
     * @throws ExchangeRateException
     */
    public function deleteByRole(string $id, int $userId): bool
    {
        [$repo] = $this->resolveContext($userId);

        $exchangeRate = $repo->find($id);

        if (!$exchangeRate) {
            throw ExchangeRateException::notFound($id);
        }

        $result = $repo->delete($id);

        // Antes no invalidaba el caché — a diferencia de create/update/
        // toggleStatus (que sí lo hacen), eliminar la única tasa ACTIVE de
        // una moneda dejaba CurrencyExchangeRateService::getActiveRate()
        // sirviendo el valor viejo cacheado hasta por 5 minutos.
        if ($result) {
            $this->forgetActiveRateCache($exchangeRate->currency_id);
        }

        return $result;
    }

    /**
     * Alternar estado activo/inactivo.
     *
     * @throws RoleResolutionException
     */
    public function changeStatusByRole(string $id, int $userId): bool
    {
        [$repo, $role] = $this->resolveContext($userId);

        $model = $repo->findOrFail($id);
        $result = $repo->toggleStatus($model);

        $this->forgetActiveRateCache($model->currency_id);

        return $result;
    }

    /* =====================================================
     | Resolución de contexto
     |=====================================================*/

    /**
     * Resuelve repositorio, rol y entidad asociada al usuario.
     *
     * @return array{0: mixed, 1: string, 2: int}
     *
     * @throws RoleResolutionException
     */
    private function resolveContext(int $userId): array
    {
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        if (!$role) {
            throw RoleResolutionException::unresolvedRoleForUser($userId);
        }

        $entityId = null;

        if (!in_array($role, ['admin', 'superadmin', 'treasurer'])) {
            $entityId = $this->roleEntityResolver->resolveEntityId($userId, $role);

            if (!$entityId) {
                throw RoleResolutionException::entityNotFoundForRole($role);
            }
        }

        return [$this->repo($role), $role, $entityId];
    }

    /**
     * Invalida el caché de "tasa activa" que CurrencyExchangeRateService
     * mantiene por moneda (TTL 5 min) — sin esto, crear/editar/activar una
     * tasa admin/superadmin podía tardar hasta 5 minutos en reflejarse para
     * cualquier consumidor de getActiveRate() (ej. BingoRoomContextService
     * armando el lobby), o peor: si un jugador cargó el lobby ANTES de que
     * existiera la tasa, el resultado "sin tasa" (null) quedaba cacheado y
     * seguía lanzando ExchangeRateException incluso después de que el admin
     * ya la hubiera activado.
     */
    private function forgetActiveRateCache(mixed $currencyId): void
    {
        if ($currencyId) {
            Cache::forget("exchange_rate:active:{$currencyId}");
        }
    }

    /**
     * Retorna el repositorio correspondiente según el rol.
     *
     * @throws RoleResolutionException
     */
    private function repo(
        string $role
    ): CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface|AdminExchangeRateRepositoryInterface {
        return match ($role) {
            'commerce'                         => $this->commerce,
            'customer'                         => $this->customer,
            'admin', 'superadmin', 'treasurer' => $this->admin,
            default                            => throw RoleResolutionException::unsupportedRole($role),
        };
    }
}

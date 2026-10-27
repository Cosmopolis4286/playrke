<?php

namespace App\Services\ExchangeRate;

use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\Roles\Admin\AdminExchangeRateRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerExchangeRateRepositoryInterface;
use App\Exceptions\Domain\Roles\RoleResolutionException;
use App\Services\Roles\RoleEntityResolver;
use App\Services\Roles\RoleUserService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Servicio para actualizar tasas de cambio desde una API externa
 * y sincronizarlas con registros de comercio y cliente.
 */
final class ExchangeRateUpdaterService
{
    public function __construct(
        private readonly RoleUserService $roleUserService,
        private readonly RoleEntityResolver $roleEntityResolver,
        private readonly AdminExchangeRateRepositoryInterface $adminExchangeRateRepository,
        private readonly CommerceExchangeRateRepositoryInterface $commerceExchangeRateRepository,
        private readonly CustomerExchangeRateRepositoryInterface $customerExchangeRateRepository,
        private readonly CurrencyRepositoryInterface $currencyRepository,
        private readonly ExchangeRateDeviationCap $deviationCap,
    ) {
    }

    /**
     * Actualiza las tasas para una entidad específica según su rol.
     *
     * Antes hacía 1 query para resolver currency_id por ISO + 1 query para
     * leer la tasa actual POR CADA moneda del lote (~10-15 monedas típicas)
     * dentro del loop — ~2 queries × N monedas × cada usuario con
     * auto-update activado en el cronjob horario. Ambas lecturas ahora se
     * resuelven en una sola query cada una para todo el lote ANTES del
     * loop; solo la escritura (updateOrCreateExchangeRate) sigue siendo
     * por fila, porque commerce/customer versionan historial (INACTIVE +
     * nueva fila) mientras admin hace upsert directo — estrategias
     * divergentes por diseño que no se pueden batchear sin cambiar esa
     * lógica de negocio.
     *
     * @param string     $role     Rol de usuario ('commerce'|'customer'|'admin')
     * @param int|string $entityId ID de la entidad (commerce_id o customer_id)
     * @param array      $data     Tasas a actualizar (iso_code => value)
     * @param int|null   $userId   usuario real que originó la actualización —
     *                             solo se usa en la rama admin/superadmin/
     *                             treasurer (commerce/customer resuelven su
     *                             propio user_id/entityId internamente en sus
     *                             repositorios, sin columna `source`)
     * @param string     $source   ExchangeRate::SOURCE_USER o SOURCE_SYSTEM
     */
    public function updateForRole(string $role, int|string|null $entityId, array $data, ?int $userId = null, string $source = 'user') : void
    {
        $repository = $this->getRepository($role);

        $isoCodes = array_filter(
            array_keys($data),
            fn ($iso) => $iso !== 'Promedio' && ($data[$iso] ?? 0) != 0,
        );

        if (empty($isoCodes)) {
            return;
        }

        // 1 query: resuelve currency_id para todos los ISO codes del lote.
        $currencyIdByIso = $this->currencyRepository
            ->findByMultipleIsoCodes($isoCodes, ['id', 'iso_code'])
            ->pluck('id', 'iso_code')
            ->all();

        $currencyIds = array_values($currencyIdByIso);

        // 1 query: resuelve la tasa vigente de TODAS esas monedas a la vez.
        $currentRates = $this->getCurrentRatesBatch($repository, $role, $entityId, $currencyIds);

        foreach ($isoCodes as $currencyIso) {
            $newRate = $data[$currencyIso];
            $currencyId = $currencyIdByIso[$currencyIso] ?? null;

            if (! $currencyId) {
                continue;
            }

            $currentRate = $currentRates[(string) $currencyId] ?? 0.0;

            $percentageChange = $currentRate != 0
                ? round((($newRate * 100) / $currentRate) - 100, 2)
                : 0;

            if (in_array($role, ['admin', 'superadmin', 'treasurer'])) {
                // $userId siempre viene poblado aquí: el único caller real,
                // updateRates(), lo pasa siempre. Sin fallback a $entityId
                // (que es null para este grupo de roles, ver líneas 39-41) —
                // un valor incorrecto en user_id (NOT NULL) es preferible a
                // uno silenciosamente equivocado.
                $repository->updateOrCreateExchangeRate($currencyId, $newRate, $percentageChange, $userId, $source);
            } else {
                // Tasa propia fuera del tope de desvío vs. la oficial: no se
                // aplica (queda la anterior; ExchangeRateAlertService avisará
                // si se desactualiza).
                if (! $this->deviationCap->allows($currencyId, (float) $newRate)) {
                    \Illuminate\Support\Facades\Log::warning('[ExchangeRateUpdater] Tasa propia omitida: supera el tope de desvío', [
                        'role' => $role, 'entity_id' => (string) $entityId, 'currency_id' => (string) $currencyId, 'rate' => $newRate,
                    ]);

                    continue;
                }
                $repository->updateOrCreateExchangeRate($entityId, $currencyId, $newRate, $percentageChange, $entityId);
            }
        }
    }

    /**
     * Actualiza las tasas de cambio para el rol activo de un usuario dado.
     *
     * @param int    $userId ID del usuario activo
     * @param array  $rates  Tasas confirmadas por el usuario (iso_code => value)
     * @param string $source exchangeRate::SOURCE_USER (default, flujo manual/
     *                       botón "Consultar") o SOURCE_SYSTEM — pasado
     *                       explícitamente por el caller (HandleExchangeRatesCommand
     *                       pasa SOURCE_SYSTEM) en vez de inferido por
     *                       app()->runningInConsole(), que también sería
     *                       true en tests/tinker y daría falsos "system"
     *
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function updateRates(int $userId, array $rates, string $source = 'user') : bool
    {
        $rolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);
        $entityId = in_array($rolePrefix, ['admin', 'superadmin', 'treasurer'])
            ? null
            : $this->roleEntityResolver->resolveEntityId($userId, $rolePrefix);

        if (empty($rates)) {
            Log::warning('No se proporcionaron tasas para actualizar.');

            return false;
        }

        try {
            $this->updateForRole($rolePrefix, $entityId, $rates, $userId, $source);

            return true;
        } catch (Throwable $e) {
            Log::error('Error actualizando tasas: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Tasas vigentes de varias monedas a la vez para una entidad, según rol
     * — delega a getCurrentRatesBatch() del repositorio correspondiente
     * (cada uno con su propia query optimizada, ver los 3 repositorios).
     *
     * @param string             $role        Rol de usuario
     * @param int|string|null    $entityId    ID de la entidad
     * @param array<int, string> $currencyIds
     *
     * @return array<string, float>
     */
    private function getCurrentRatesBatch(
        CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface|AdminExchangeRateRepositoryInterface $repository,
        string $role,
        int|string|null $entityId,
        array $currencyIds,
    ) : array {
        if (in_array($role, ['admin', 'superadmin', 'treasurer'])) {
            return $repository->getCurrentRatesBatch($currencyIds);
        }
        if ($role === 'commerce') {
            return $repository->getCurrentRatesBatch($entityId, $currencyIds);
        }
        if ($role === 'customer') {
            return $repository->getCurrentRatesBatch($entityId, $currencyIds);
        }

        // getRepository() ya lanza RoleResolutionException para cualquier rol
        // no soportado antes de llegar aquí — este camino es inalcanzable en
        // la práctica, pero se deja explícito en vez de un return implícito.
        throw RoleResolutionException::unsupportedRole($role);
    }

    /**
     * Obtiene el repositorio correspondiente según el rol.
     *
     * @param string $role Rol ('commerce'|'customer')
     *
     * @return CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface
     */
    private function getRepository(string $role) : CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface|AdminExchangeRateRepositoryInterface
    {
        return match ($role) {
            'admin', 'superadmin', 'treasurer' => $this->adminExchangeRateRepository,
            'commerce' => $this->commerceExchangeRateRepository,
            'customer' => $this->customerExchangeRateRepository,
            default => throw RoleResolutionException::unsupportedRole($role),
        };
    }
}

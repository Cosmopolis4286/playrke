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

/**
 * Servicio para actualizar tasas de cambio desde una API externa
 * y sincronizarlas con registros de comercio y cliente.
 */
final class ExchangeRateUpdaterService
{
    public function __construct(
        protected readonly RoleUserService $roleUserService,
        protected readonly RoleEntityResolver $roleEntityResolver,
        protected readonly AdminExchangeRateRepositoryInterface $adminExchangeRateRepository,
        protected readonly CommerceExchangeRateRepositoryInterface $commerceExchangeRateRepository,
        protected readonly CustomerExchangeRateRepositoryInterface $customerExchangeRateRepository,
        protected readonly CurrencyRepositoryInterface $currencyRepository,
    ) {}

    /**
     * Actualiza las tasas de cambio para el rol activo de un usuario dado.
     *
     * @param int $userId ID del usuario activo
     * @param array $rates Tasas confirmadas por el usuario (iso_code => value)
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function updateRates(int $userId, array $rates): bool
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
            $this->updateForRole($rolePrefix, $entityId, $rates);
            return true;
        } catch (\Throwable $e) {
            Log::error('Error actualizando tasas: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza las tasas para una entidad específica según su rol.
     *
     * @param string $role Rol de usuario ('commerce'|'customer'|'admin')
     * @param int|string $entityId ID de la entidad (commerce_id o customer_id)
     * @param array $data Tasas a actualizar (iso_code => value)
     * @return void
     */
    public function updateForRole(string $role, int|string|null $entityId, array $data): void
    {
        $repository = $this->getRepository($role);

        foreach ($data as $currencyIso => $newRate) {
            if ($currencyIso === 'Promedio' || $newRate == 0) {
                continue;
            }

            $currencyId = $this->currencyRepository->getValueByISOCode($currencyIso, 'id');
            if (!$currencyId) {
                continue;
            }

            $currentRate = $this->getCurrentRate($repository, $role, $entityId, $currencyId);

            $percentageChange = $currentRate != 0
                ? round((($newRate * 100) / $currentRate) - 100, 2)
                : 0;

            if (in_array($role, ['admin', 'superadmin', 'treasurer'])) {
                $repository->updateOrCreateExchangeRate($currencyId, $newRate, $percentageChange);
            } else {
                $repository->updateOrCreateExchangeRate($entityId, $currencyId, $newRate, $percentageChange, $entityId);
            }
        }
    }

    /**
     * Obtiene la tasa actual de una moneda para una entidad.
     *
     * @param CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface|AdminExchangeRateRepositoryInterface $repository
     * @param string $role Rol de usuario
     * @param int|string $entityId ID de la entidad
     * @param string $currencyId ID de la moneda
     * @return float Tasa actual o 0 si no existe
     */
    protected function getCurrentRate(
        CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface|AdminExchangeRateRepositoryInterface $repository,
        string $role,
        int|string|null $entityId,
        string $currencyId
    ): float {

        if (in_array($role, ['admin', 'superadmin', 'treasurer'])) {
            return (float) ($repository->getCurrencyValue($currencyId, 'amount') ?? 0);
        } else if ($role === 'commerce') {
            return (float) ($repository->getCommerceCurrencyValue($entityId, $currencyId, 'amount') ?? 0);
        } else if ($role === 'customer') {
            return (float) ($repository->getCustomerCurrencyValue($entityId, $currencyId, 'amount') ?? 0);
        }

        // getRepository() ya lanza RoleResolutionException para cualquier rol
        // no soportado antes de llegar aquí — este camino es inalcanzable en
        // la práctica, pero antes devolvía null implícito desde una función
        // con return type float (TypeError silencioso si algún día ambos
        // métodos se desacoplan).
        throw RoleResolutionException::unsupportedRole($role);
    }

    /**
     * Obtiene el repositorio correspondiente según el rol.
     *
     * @param string $role Rol ('commerce'|'customer')
     * @return CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface
     */
    protected function getRepository(string $role): CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface|AdminExchangeRateRepositoryInterface
    {
        return match ($role) {
            'admin', 'superadmin', 'treasurer' => $this->adminExchangeRateRepository,
            'commerce'                         => $this->commerceExchangeRateRepository,
            'customer'                         => $this->customerExchangeRateRepository,
            default                            => throw RoleResolutionException::unsupportedRole($role),
        };
    }
}

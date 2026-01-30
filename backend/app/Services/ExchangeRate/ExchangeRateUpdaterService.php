<?php

namespace App\Services\ExchangeRate;

use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerExchangeRateRepositoryInterface;
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
        $entityId = $this->roleUserService->getUserRoleEntityId($userId, $rolePrefix);

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
     * @param string $role Rol de usuario ('commerce'|'customer')
     * @param int|string $entityId ID de la entidad (commerce_id o customer_id)
     * @param array $data Tasas a actualizar (iso_code => value)
     * @return void
     */
    public function updateForRole(string $role, int|string $entityId, array $data): void
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

            $repository->updateOrCreateExchangeRate($entityId, $currencyId, $newRate, $percentageChange);
        }
    }

    /**
     * Obtiene la tasa actual de una moneda para una entidad.
     *
     * @param CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface $repository
     * @param string $role Rol de usuario
     * @param int|string $entityId ID de la entidad
     * @param string $currencyId ID de la moneda
     * @return float Tasa actual o 0 si no existe
     */
    protected function getCurrentRate(
        CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface $repository,
        string $role,
        int|string $entityId,
        string $currencyId
    ): float {
        if ($role === 'commerce') {
            return (float) ($repository->getCommerceCurrencyValue($entityId, $currencyId, 'amount') ?? 0);
        }

        return (float) ($repository->getCustomerCurrencyValue($entityId, $currencyId, 'amount') ?? 0);
    }

    /**
     * Obtiene el repositorio correspondiente según el rol.
     *
     * @param string $role Rol ('commerce'|'customer')
     * @return CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface
     */
    protected function getRepository(string $role): CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface
    {
        return $role === 'commerce'
            ? $this->commerceExchangeRateRepository
            : $this->customerExchangeRateRepository;
    }
}

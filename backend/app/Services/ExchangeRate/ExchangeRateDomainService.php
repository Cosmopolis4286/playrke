<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerExchangeRateRepositoryInterface;
use App\Exceptions\Domain\ExchangeRate\ExchangeRateException;

/**
 * Domain service encargado de resolver la tasa de cambio aplicable a un rol
 * (comercio, cliente, etc.) y de calcular variaciones porcentuales.
 *
 * exchange_rates (vía CurrencyExchangeRateService) es la única fuente de
 * verdad ÚLTIMA: cada rol puede tener su propia tasa personalizada
 * (ej. commerces_exchange_rates, con margen propio del comercio), pero si
 * ese rol no tiene una tasa activa configurada para la moneda, se HEREDA
 * la tasa oficial — nunca se asume 1:1 en silencio.
 */
final class ExchangeRateDomainService
{
    public function __construct(
        private readonly \App\Services\Roles\RoleUserService $roleUserService,
        private readonly \App\Services\Roles\RoleEntityResolver $roleEntityResolver,
        private readonly CommerceExchangeRateRepositoryInterface $commerceExchangeRate,
        private readonly CurrencyExchangeRateService $officialExchangeRate,
        private readonly CustomerExchangeRateRepositoryInterface $customerExchangeRate,
    ) {}
    /**
     * Calcula la variación porcentual entre un monto anterior y uno nuevo.
     *
     * Fórmula:
     *   ((nuevo * 100) / anterior) - 100
     *
     * Ejemplo:
     *   old = 100
     *   new = 110
     *   resultado = 10.00
     *
     * @param float $old Monto anterior (base del cálculo). Debe ser mayor a 0.
     * @param float $new Monto nuevo a comparar.
     *
     * @return float Variación porcentual redondeada a 2 decimales.
     *
     * @throws ExchangeRateException Si el monto anterior es menor o igual a cero.
     */
    public function calculatePercentage(float $old, float $new): float
    {
        if ($old <= 0) {
            throw ExchangeRateException::invalidPreviousAmount();
        }

        return round((($new * 100) / $old) - 100, 2);
    }

    /**
     * Obtiene la tasa de cambio aplicable para una moneda, según el rol
     * activo del usuario: primero su tasa propia (ej. comercio), y si no
     * existe, la oficial de la plataforma (exchange_rates).
     *
     * @param int $currencyId ID de la moneda para la cual se desea obtener el tipo de cambio.
     * @param int $userId ID del usuario para contextos específicos.
     *
     * @return float Tipo de cambio aplicable para la moneda especificada.
     *
     * @throws ExchangeRateException Si no hay tasa propia del rol ni oficial para la moneda.
     */
    public function getExchangeRateByCurrency(int $currencyId, int $userId): float
    {
        return $this->resolveExchangeRateByRole((string) $userId, $currencyId);
    }

    /**
     * Resuelve la tasa de cambio según el prefijo de rol activo:
     * - commerce: su tasa propia ACTIVA en commerces_exchange_rates;
     * - customer: su tasa propia ACTIVA en customers_exchange_rates;
     *   en ambos casos, sin tasa propia (o desactivada) hereda la oficial.
     * - cualquier otro rol (admin, etc.): directamente la oficial.
     *
     * @param string $userId ID del usuario para contextos específicos.
     * @param int|string $currencyId ID de la moneda para la cual se desea obtener el tipo de cambio.
     *
     * @return float Tipo de cambio para la moneda especificada.
     *
     * @throws ExchangeRateException Si no hay tasa propia del rol ni oficial para la moneda.
     */
    protected function resolveExchangeRateByRole(string $userId, int|string $currencyId): float
    {
        // Resolver el prefijo de rol activo para el usuario
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        // Comercializadora y cliente API (creadores de salas): su tasa propia
        // ACTIVA de esa moneda, si la tienen.
        if (in_array($role, ['commerce', 'customer'], true)) {
            $entityId = $this->roleEntityResolver->resolveEntityId($userId, $role);
            $ownRate  = $entityId
                ? ($role === 'commerce'
                    ? $this->commerceExchangeRate->getCommerceCurrencyValue($entityId, $currencyId)
                    : $this->customerExchangeRate->getCustomerCurrencyValue($entityId, (string) $currencyId))
                : null;

            if ($ownRate !== null && (float) $ownRate > 0) {
                return (float) $ownRate;
            }
        }

        // Sin tasa propia del rol (o rol sin tasa personalizada): heredar la oficial.
        // Lanza ExchangeRateException si tampoco existe ahí.
        return $this->officialExchangeRate->getActiveRate($currencyId);
    }
}

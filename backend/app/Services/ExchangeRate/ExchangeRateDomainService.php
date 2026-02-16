<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use DomainException;

/**
 * Domain service encargado de manejar la lógica relacionada con los tipos de cambio y sus variaciones.
 */
final class ExchangeRateDomainService
{
    public function __construct(
        private readonly \App\Services\Roles\RoleUserService $roleUserService,
        private readonly \App\Services\Roles\RoleEntityResolver $roleEntityResolver,
        private readonly CommerceExchangeRateRepositoryInterface $commerceExchangeRate,
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
     * @throws DomainException Si el monto anterior es menor o igual a cero.
     */
    public function calculatePercentage(float $old, float $new): float
    {
        if ($old <= 0) {
            throw new DomainException('Monto anterior inválido');
        }

        return round((($new * 100) / $old) - 100, 2);
    }

    /** Obtiene el tipo de cambio para una moneda específica.
     *
     * @param int $currencyId ID de la moneda para la cual se desea obtener el tipo de cambio.
     * @param int $userId ID del usuario para contextos específicos.
     *
     * @return float Tipo de cambio actual para la moneda especificada.
     *
     * @throws DomainException Si no se puede obtener el tipo de cambio.
     */
    public function getExchangeRateByCurrency(int $currencyId, int $userId): float
    {
        return $this->resolveExchangeRateByRole($userId, $currencyId);
    }

    /**
     * Resuelve la tasas de cambio según el prefijo de rol activo.
     *
     * @param string $userId ID del usuario para contextos específicos.
     * @param int $currencyId ID de la moneda para la cual se desea obtener el tipo de cambio.
     *
     * @return float Tipo de cambio para la moneda especificada.
     */
    protected function resolveExchangeRateByRole(string $userId, $currencyId): float
    {
        // Resolver el prefijo de rol activo para el usuario
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        // Resolver la entidad asociada al rol (commerce, customer, etc.)
        $entityId = $this->roleEntityResolver->resolveEntityId($userId, $role);

        return match ($role) {
            'commerce' => $this->commerceExchangeRate->getAvailableExchangeRates($entityId, $currencyId),
            'customer', 'admin' => collect(),
            default => collect(),
        };
    }
}

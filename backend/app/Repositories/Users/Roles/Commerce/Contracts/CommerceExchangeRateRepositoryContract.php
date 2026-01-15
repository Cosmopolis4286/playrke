<?php

namespace App\Repositories\Users\Roles\Commerce\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\RolesExchangeRate;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de tasas de cambio de comercios
 */
interface CommerceExchangeRateRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtiene todas las tasas de cambio por moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getAllExchangeRatesByCurrency($commerceId, $currencyId): Collection;

    /**
     * Obtiene valor específico de tasa de comercio y moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getCommerceCurrencyValue($commerceId, $currencyId, string $field = 'exchange_rate');

    /**
     * Obtiene todas las tasas de cambio de un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return Collection
     */
    public function getAllExchangeRates($commerceId): Collection;

    /**
     * Obtiene valores de tasas por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getValuesByCurrency($currencyId): Collection;

    /**
     * Obtiene tasas de cambio disponibles
     *
     * @param mixed $commerceId ID del comercio
     * @return Collection
     */
    public function getAvailableExchangeRates($commerceId): Collection;

    /**
     * Obtiene monedas por comercio
     *
     * @param mixed $commerceId ID del comercio
     * @param string|null $status Estado de las tasas
     * @return array
     */
    public function getCurrenciesByCommerce($commerceId, ?string $status = 'ACTIVE'): array;

    /**
     * Actualiza o crea nueva tasa de cambio
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     * @param float $amount Monto de la tasa
     * @param float $percentage Porcentaje
     * @return bool
     */
    public function updateOrCreateExchangeRate($commerceId, $currencyId, float $amount, float $percentage = 0): bool;

    /**
     * Obtiene registros por comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return Collection
     */
    public function getByCommerce($commerceId): Collection;

    /**
     * Obtiene registros por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getByCurrency($currencyId): Collection;

    /**
     * Obtiene tasas activas
     *
     * @return Collection
     */
    public function getActiveRates(): Collection;

    /**
     * Obtiene tasas inactivas
     *
     * @return Collection
     */
    public function getInactiveRates(): Collection;

    /**
     * Obtiene tasas obsoletas
     *
     * @return Collection
     */
    public function getOldRates(): Collection;

    /**
     * Actualiza estado de una tasa
     *
     * @param mixed $id ID de la tasa
     * @param string $status Nuevo estado
     * @return bool
     */
    public function updateStatus($id, string $status): bool;

    /**
     * Marca tasas como obsoletas por comercio y moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     * @return int Número de registros actualizados
     */
    public function markAsOld($commerceId, $currencyId): int;

    /**
     * Obtiene la tasa más reciente por comercio y moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     * @return CommerceExchangeRate|null
     */
    public function getLatestRate($commerceId, $currencyId): ?CommerceExchangeRate;
}

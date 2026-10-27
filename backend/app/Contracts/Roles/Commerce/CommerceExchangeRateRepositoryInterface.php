<?php

namespace App\Contracts\Roles\Commerce;

use App\Contracts\ExchangeRate\RoleExchangeRateRepositoryInterface;
use App\Models\Roles\Commerce\CommerceExchangeRate;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de tasas de cambio de comercios
 */
interface CommerceExchangeRateRepositoryInterface extends RoleExchangeRateRepositoryInterface
{
    /**
     * Obtiene tasas activas
     */
    public function getActiveRates() : Collection;

    /**
     * Obtiene todas las tasas de cambio de un comercio
     *
     * @param mixed $commerceId ID del comercio
     */
    public function getAllExchangeRates($commerceId) : Collection;

    /**
     * Obtiene todas las tasas de cambio por moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     */
    public function getAllExchangeRatesByCurrency($commerceId, $currencyId) : Collection;

    /**
     * Obtiene tasas de cambio disponibles
     *
     * @param mixed $commerceId ID del comercio
     */
    public function getAvailableExchangeRates($commerceId) : Collection;

    /**
     * Obtiene registros por comercio
     *
     * @param mixed $commerceId ID del comercio
     */
    public function getByCommerce($commerceId) : Collection;

    /**
     * Obtiene valor específico de tasa de comercio y moneda
     *
     * @param mixed  $commerceId ID del comercio
     * @param mixed  $currencyId ID de la moneda
     * @param string $field      Campo a obtener
     *
     * @return mixed
     */
    public function getCommerceCurrencyValue($commerceId, $currencyId, string $field = 'amount');

    /**
     * Obtiene monedas por comercio
     *
     * @param mixed       $commerceId ID del comercio
     * @param string|null $status     Estado de las tasas
     */
    public function getCurrenciesByCommerce($commerceId, ?string $status = 'ACTIVE') : array;

    /**
     * Tasas ACTIVE actuales de un comercio para varias monedas a la vez
     * (currency_id => amount) — usado por ExchangeRateUpdaterService::
     * updateForRole() para resolver TODAS las tasas vigentes en una sola
     * query en vez de una por moneda dentro del loop de actualización masiva.
     *
     * @param mixed              $commerceId  ID del comercio
     * @param array<int, string> $currencyIds
     *
     * @return array<string, float>
     */
    public function getCurrentRatesBatch($commerceId, array $currencyIds) : array;

    /**
     * Obtiene el historial completo de tasas por comercio y moneda.
     * Incluye todos los estados (ACTIVE, INACTIVE, OLD, etc.).
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     */
    public function getHistoryByCurrency($commerceId, $currencyId) : Collection;

    /**
     * Obtiene tasas inactivas
     */
    public function getInactiveRates() : Collection;

    /**
     * Obtiene la tasa más reciente por comercio y moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     */
    public function getLatestRate($commerceId, $currencyId) : ?CommerceExchangeRate;

    /**
     * Obtiene tasas obsoletas
     */
    public function getOldRates() : Collection;

    /**
     * Obtiene valores de tasas por moneda
     *
     * @param mixed $currencyId ID de la moneda
     */
    public function getValuesByCurrency($currencyId) : Collection;

    /**
     * Marca tasas como obsoletas por comercio y moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     *
     * @return int Número de registros actualizados
     */
    public function markAsOld($commerceId, $currencyId) : int;

    /**
     * Actualiza o crea nueva tasa de cambio
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     * @param float $amount     Monto de la tasa
     * @param float $percentage Porcentaje
     */
    public function updateOrCreateExchangeRate($commerceId, $currencyId, float $amount, float $percentage = 0) : bool;

    /**
     * Actualiza estado de una tasa
     *
     * @param mixed  $id     ID de la tasa
     * @param string $status Nuevo estado
     */
    public function updateStatus($id, string $status) : bool;
}

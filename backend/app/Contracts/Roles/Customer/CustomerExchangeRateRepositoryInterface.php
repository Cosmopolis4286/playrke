<?php

namespace App\Contracts\Roles\Customer;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Roles\Customer\CustomerExchangeRate;
use Illuminate\Database\Eloquent\Collection;

interface CustomerExchangeRateRepositoryInterface extends BaseRepositoryInterface
{
    public function activateRate($customerId, string $currencyId) : bool;

    public function deactivateRate($customerId, string $currencyId) : bool;

    public function existsCurrencyByCustomer($customerId, string $currencyId) : bool;

    public function getActiveRates($customerId) : Collection;

    public function getAllExchangeRates($customerId) : Collection;

    public function getAllExchangeRatesByCurrency($customerId, string $currencyId) : Collection;

    /**
     * Códigos ISO de las monedas con tasa registrada (cualquier status,
     * incluye OLD/INACTIVE — no confundir con "activas") por el customer —
     * usado para filtrar las tasas crudas de la API externa a solo las
     * monedas que este customer gestiona, mismo criterio que
     * CommerceCurrencyRepositoryInterface::getAvailableCurrenciesIsoCodes().
     */
    public function getAvailableCurrenciesIsoCodes($customerId) : array;

    public function getAvailableExchangeRates($customerId) : Collection;

    public function getBestRateForCustomer($customerId, string $currencyId) : ?CustomerExchangeRate;

    public function getByCustomerCurrency($customerId, string $currencyId) : ?CustomerExchangeRate;

    public function getCurrenciesByCustomer($customerId, string $status = 'ACTIVE') : array;

    public function getCurrenciesIDsByCustomer($customerId, string $status = 'ACTIVE') : Collection;

    /**
     * Tasas ACTIVE actuales de un customer para varias monedas a la vez
     * (currency_id => amount) — usado por ExchangeRateUpdaterService::
     * updateForRole() para resolver TODAS las tasas vigentes en una sola
     * query en vez de una por moneda dentro del loop de actualización masiva.
     *
     * @param mixed              $customerId  ID del customer
     * @param array<int, string> $currencyIds
     *
     * @return array<string, float>
     */
    public function getCurrentRatesBatch($customerId, array $currencyIds) : array;

    public function getCustomerCurrencyValue($customerId, string $currencyId, string $column = 'amount');

    public function getInactiveRates($customerId) : Collection;

    public function getLatestRateByCustomerCurrency($customerId, string $currencyId) : ?CustomerExchangeRate;

    public function getRateHistory($customerId, string $currencyId) : Collection;

    public function getValuesByCurrency(string $currencyId) : Collection;

    public function updateOrCreateExchangeRate($customerId, string $currencyId, float $amount, float $percentage = 0) : CustomerExchangeRate;
}

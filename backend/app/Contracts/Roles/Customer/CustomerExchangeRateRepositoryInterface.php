<?php

namespace App\Contracts\Roles\Customer;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Pivots\Roles\Customer\CustomerExchangeRate;
use Illuminate\Database\Eloquent\Collection;

interface CustomerExchangeRateRepositoryInterface extends BaseRepositoryInterface
{
    public function getAllExchangeRatesByCurrency($customerId, string $currencyId): Collection;

    public function getByCustomerCurrency($customerId, string $currencyId): ?CustomerExchangeRate;

    public function getCustomerCurrencyValue($customerId, string $currencyId, string $column = 'exchange_rate');

    public function getAllExchangeRates($customerId): Collection;

    public function getValuesByCurrency(string $currencyId): Collection;

    public function getAvailableExchangeRates($customerId): Collection;

    public function getCurrenciesByCustomer($customerId, string $status = 'ACTIVE'): array;

    public function getCurrenciesIDsByCustomer($customerId, string $status = 'ACTIVE'): Collection;

    public function existsCurrencyByCustomer($customerId, string $currencyId): bool;

    public function updateOrCreateExchangeRate($customerId, string $currencyId, float $amount, float $percentage = 0): CustomerExchangeRate;

    public function getActiveRates($customerId): Collection;

    public function getInactiveRates($customerId): Collection;

    public function activateRate($customerId, string $currencyId): bool;

    public function deactivateRate($customerId, string $currencyId): bool;

    public function getLatestRateByCustomerCurrency($customerId, string $currencyId): ?CustomerExchangeRate;

    public function getRateHistory($customerId, string $currencyId): Collection;

    public function getBestRateForCustomer($customerId, string $currencyId): ?CustomerExchangeRate;
}

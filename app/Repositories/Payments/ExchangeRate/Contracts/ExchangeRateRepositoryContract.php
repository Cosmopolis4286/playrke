<?php

namespace App\Repositories\Payments\ExchangeRate\Contracts;

use App\Repositories\Contracts\BaseRepositoryContract;
use App\Models\Payments\ExchangeRate\ExchangeRate;
use Illuminate\Database\Eloquent\Collection;

interface ExchangeRateRepositoryContract extends BaseRepositoryContract
{
    public function getByCurrency($currencyId): Collection;

    public function getLatestByCurrency($currencyId): ?ExchangeRate;

    public function getCurrentRate($currencyId): ?float;

    public function createExchangeRate($currencyId, float $value): ExchangeRate;

    public function updateExchangeRate($currencyId, float $value): bool;

    public function getRateHistory($currencyId, int $limit = 10): Collection;

    public function getExchangeRatesInRange(float $minValue, float $maxValue): Collection;

    public function getCurrenciesWithRates(): Collection;

    public function getHighestRate($currencyId): ?ExchangeRate;

    public function getLowestRate($currencyId): ?ExchangeRate;

    public function getAverageRate($currencyId): float;

    public function getRatesAboveValue(float $value): Collection;

    public function getRatesBelowValue(float $value): Collection;

    public function updateOrCreateRate($currencyId, float $value): ExchangeRate;

    public function deleteRatesByCurrency($currencyId): int;

    public function getRecentRates(int $hours = 24): Collection;

    public function searchByCurrencyCode(string $currencyCode): Collection;
}
<?php

namespace App\Repositories\Roles\Customer;

use App\Contracts\Roles\Customer\CustomerExchangeRateRepositoryInterface;
use App\Models\Roles\Customer\CustomerExchangeRate;
use App\Repositories\ExchangeRate\RoleExchangeRateRepository;
use Illuminate\Database\Eloquent\Collection;

class CustomerExchangeRateRepository extends RoleExchangeRateRepository implements CustomerExchangeRateRepositoryInterface
{
    /**
     * Sin este constructor, $this->model (declarado en BaseRepository) nunca
     * se inicializaba — bug preexistente nunca ejercitado porque el rol
     * 'customer' no llegaba a esta clase en ningún flujo real hasta ahora
     * (ver fix en ExchangeRateContextService::resolveExchangeRatesByRole()).
     * Mismo patrón que CommerceExchangeRateRepository/AdminExchangeRateRepository.
     */
    public function __construct(CustomerExchangeRate $model)
    {
        $this->model = $model;
    }

    public function getAllExchangeRatesByCurrency($customerId, string $currencyId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->where('currency_id', $currencyId)
            ->orderByDesc('id')
            ->get(['currency_id', 'amount', 'percentage']);
    }

    public function getByCustomerCurrency($customerId, string $currencyId): ?CustomerExchangeRate
    {
        return $this->model->where('customer_id', $customerId)
            ->where('currency_id', $currencyId)
            ->orderByDesc('id')
            ->first();
    }

    public function getCustomerCurrencyValue($customerId, string $currencyId, string $column = 'amount')
    {
        return $this->model->where('customer_id', $customerId)
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->value($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentRatesBatch($customerId, array $currencyIds): array
    {
        if (empty($currencyIds)) {
            return [];
        }

        $rows = $this->model
            ->where('customer_id', $customerId)
            ->whereIn('currency_id', $currencyIds)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->get(['currency_id', 'amount']);

        $result = [];
        foreach ($rows as $row) {
            $key = (string) $row->currency_id;
            if (!isset($result[$key])) {
                $result[$key] = (float) $row->amount;
            }
        }

        return $result;
    }

    public function getAllExchangeRates($customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->get(['currency_id', 'amount']);
    }

    public function getValuesByCurrency(string $currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->pluck('amount');
    }

    public function getAvailableExchangeRates($customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->where('status', '<>', 'INACTIVE')
            ->get();
    }

    public function getAvailableCurrenciesIsoCodes($customerId): array
    {
        return $this->model
            ->join('currencies', 'currencies.id', '=', 'customers_exchange_rates.currency_id')
            ->where('customers_exchange_rates.customer_id', $customerId)
            ->where('customers_exchange_rates.status', 'ACTIVE')
            ->distinct()
            ->pluck('currencies.iso_code')
            ->toArray();
    }

    public function getCurrenciesByCustomer($customerId, string $status = 'ACTIVE'): array
    {
        $query = $this->model->select('currency_id')->where('customer_id', $customerId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->distinct()->pluck('currency_id')->toArray();
    }

    public function getCurrenciesIDsByCustomer($customerId, string $status = 'ACTIVE'): Collection
    {
        return $this->model->select('id', 'currency_id')
            ->where('customer_id', $customerId)
            ->where('status', $status)
            ->get();
    }

    public function existsCurrencyByCustomer($customerId, string $currencyId): bool
    {
        return $this->model->where('customer_id', $customerId)
            ->where('currency_id', $currencyId)
            ->exists();
    }

    public function updateOrCreateExchangeRate($customerId, string $currencyId, float $amount, float $percentage = 0): CustomerExchangeRate
    {
        $lastRate = $this->model->where('customer_id', $customerId)
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->first();

        if (!$lastRate || $lastRate->amount != $amount) {
            if ($lastRate) {
                $lastRate->update(['status' => 'INACTIVE']);
            }

            return $this->model->create([
                'customer_id' => $customerId,
                'currency_id' => $currencyId,
                'amount' => $amount,
                'percentage' => $percentage,
                'status' => 'ACTIVE'
            ]);
        }

        return $lastRate;
    }

    public function getActiveRates($customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->where('status', 'ACTIVE')
            ->get();
    }

    public function getInactiveRates($customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->where('status', 'INACTIVE')
            ->get();
    }

    public function activateRate($customerId, string $currencyId): bool
    {
        return $this->model->where('customer_id', $customerId)
            ->where('currency_id', $currencyId)
            ->update(['status' => 'ACTIVE']) > 0;
    }

    public function deactivateRate($customerId, string $currencyId): bool
    {
        return $this->model->where('customer_id', $customerId)
            ->where('currency_id', $currencyId)
            ->update(['status' => 'INACTIVE']) > 0;
    }

    public function getLatestRateByCustomerCurrency($customerId, string $currencyId): ?CustomerExchangeRate
    {
        return $this->model->where('customer_id', $customerId)
            ->where('currency_id', $currencyId)
            ->latest()
            ->first();
    }

    public function getRateHistory($customerId, string $currencyId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->where('currency_id', $currencyId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getBestRateForCustomer($customerId, string $currencyId): ?CustomerExchangeRate
    {
        return $this->model->where('customer_id', $customerId)
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderBy('amount', 'asc')
            ->first();
    }
}

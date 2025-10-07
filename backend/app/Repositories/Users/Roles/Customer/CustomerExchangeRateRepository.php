<?php

namespace App\Repositories\Users\Roles\Customer;

use App\Models\Users\Roles\Customer\CustomerExchangeRate;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Users\Roles\Customer\Contracts\CustomerExchangeRateRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

class CustomerExchangeRateRepository extends BaseRepository implements CustomerExchangeRateRepositoryContract
{
    public function __construct(CustomerExchangeRate $model)
    {
        parent::__construct($model);
    }

    public function getAllExchangeRatesByCurrency($customerId, string $currencyId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->where('currency_id', $currencyId)
            ->orderByDesc('id')
            ->get(['currency_id', 'exchange_rate', 'percentage']);
    }

    public function getByCustomerCurrency($customerId, string $currencyId): ?CustomerExchangeRate
    {
        return $this->model->where('customer_id', $customerId)
            ->where('currency_id', $currencyId)
            ->orderByDesc('id')
            ->first();
    }

    public function getCustomerCurrencyValue($customerId, string $currencyId, string $column = 'exchange_rate')
    {
        return $this->model->where('customer_id', $customerId)
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->value($column);
    }

    public function getAllExchangeRates($customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->get(['currency_id', 'exchange_rate']);
    }

    public function getValuesByCurrency(string $currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->pluck('exchange_rate');
    }

    public function getAvailableExchangeRates($customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->where('status', '<>', 'INACTIVE')
            ->get();
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

        if (!$lastRate || $lastRate->exchange_rate != $amount) {
            if ($lastRate) {
                $lastRate->update(['status' => 'INACTIVE']);
            }

            return $this->model->create([
                'customer_id' => $customerId,
                'currency_id' => $currencyId,
                'exchange_rate' => $amount,
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
            ->orderBy('exchange_rate', 'asc')
            ->first();
    }
}
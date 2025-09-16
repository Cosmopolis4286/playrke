<?php

namespace App\Repositories\Payments\ExchangeRate;

use App\Models\Payments\ExchangeRate\ExchangeRate;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Payments\ExchangeRate\Contracts\ExchangeRateRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class ExchangeRateRepository extends BaseRepository implements ExchangeRateRepositoryContract
{
    public function __construct(ExchangeRate $model)
    {
        parent::__construct($model);
    }

    public function getByCurrency($currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)->get();
    }

    public function getLatestByCurrency($currencyId): ?ExchangeRate
    {
        return $this->model->where('currency_id', $currencyId)
            ->latest()
            ->first();
    }

    public function getCurrentRate($currencyId): ?float
    {
        $exchangeRate = $this->getLatestByCurrency($currencyId);
        return $exchangeRate ? $exchangeRate->value : null;
    }

    public function createExchangeRate($currencyId, float $value): ExchangeRate
    {
        return $this->model->create([
            'currency_id' => $currencyId,
            'value' => $value
        ]);
    }

    public function updateExchangeRate($currencyId, float $value): bool
    {
        $latest = $this->getLatestByCurrency($currencyId);

        if ($latest) {
            return $latest->update(['value' => $value]);
        }

        $this->createExchangeRate($currencyId, $value);
        return true;
    }

    public function getRateHistory($currencyId, int $limit = 10): Collection
    {
        return $this->model->where('currency_id', $currencyId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getExchangeRatesInRange(float $minValue, float $maxValue): Collection
    {
        return $this->model->whereBetween('value', [$minValue, $maxValue])->get();
    }

    public function getCurrenciesWithRates(): Collection
    {
        return $this->model->with('currency')
            ->select('currency_id')
            ->distinct()
            ->get();
    }

    public function getHighestRate($currencyId): ?ExchangeRate
    {
        return $this->model->where('currency_id', $currencyId)
            ->orderBy('value', 'desc')
            ->first();
    }

    public function getLowestRate($currencyId): ?ExchangeRate
    {
        return $this->model->where('currency_id', $currencyId)
            ->orderBy('value', 'asc')
            ->first();
    }

    public function getAverageRate($currencyId): float
    {
        return $this->model->where('currency_id', $currencyId)
            ->avg('value') ?? 0.0;
    }

    public function getRatesAboveValue(float $value): Collection
    {
        return $this->model->where('value', '>', $value)->get();
    }

    public function getRatesBelowValue(float $value): Collection
    {
        return $this->model->where('value', '<', $value)->get();
    }

    public function updateOrCreateRate($currencyId, float $value): ExchangeRate
    {
        return $this->model->updateOrCreate(
            ['currency_id' => $currencyId],
            ['value' => $value]
        );
    }

    public function deleteRatesByCurrency($currencyId): int
    {
        return $this->model->where('currency_id', $currencyId)->delete();
    }

    public function getRecentRates(int $hours = 24): Collection
    {
        $since = Carbon::now()->subHours($hours);

        return $this->model->where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function searchByCurrencyCode(string $currencyCode): Collection
    {
        return $this->model->whereHas('currency', function($query) use ($currencyCode) {
            $query->where('code', 'LIKE', "%{$currencyCode}%");
        })->get();
    }
}
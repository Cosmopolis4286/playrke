<?php

namespace App\Repositories;

use App\Repositories\BaseRepository;
use App\Contracts\ExchangeRateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Repositorio para la gestión de tipos de cambio.
 *
 * Implementa las operaciones específicas para el modelo de tasas de cambio
 * incluyendo gestión de valores, conversiones y estadísticas.
 */
class ExchangeRateRepository extends BaseRepository implements ExchangeRateRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getByCurrency(string $currencyId): ?Model
    {
        return $this->model->where('currency_id', $currencyId)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllWithCurrency(): Collection
    {
        return $this->model->with('currency')->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByValueRange(float $minValue, float $maxValue): Collection
    {
        return $this->model->whereBetween('value', [$minValue, $maxValue])->get();
    }

    /**
     * {@inheritDoc}
     */
    public function existsForCurrency(string $currencyId): bool
    {
        return $this->model->where('currency_id', $currencyId)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function createExchangeRate(string $currencyId, float $value): Model
    {
        // Validar datos antes de crear
        $errors = $this->validateExchangeRateData([
            'currency_id' => $currencyId,
            'value' => $value
        ]);

        if (!empty($errors)) {
            throw new \InvalidArgumentException('Datos de tipo de cambio inválidos: ' . json_encode($errors));
        }

        return $this->model->create([
            'currency_id' => $currencyId,
            'value' => $value
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updateValue(string $exchangeRateId, float $value): bool
    {
        if (!$this->isValidValue($value)) {
            throw new \InvalidArgumentException('Valor de tipo de cambio inválido');
        }

        return $this->model->where('id', $exchangeRateId)
            ->update(['value' => $value]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateByCurrency(string $currencyId, float $value): bool
    {
        if (!$this->isValidValue($value)) {
            throw new \InvalidArgumentException('Valor de tipo de cambio inválido');
        }

        return $this->model->where('currency_id', $currencyId)
            ->update(['value' => $value]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteExchangeRate(string $exchangeRateId): bool
    {
        return $this->model->where('id', $exchangeRateId)->delete() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteByCurrency(string $currencyId): bool
    {
        return $this->model->where('currency_id', $currencyId)->delete() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getExchangeRateStatistics(): array
    {
        $total = $this->model->count();
        $avgValue = $this->model->avg('value');
        $maxValue = $this->model->max('value');
        $minValue = $this->model->min('value');
        $recentUpdates = $this->model->where('updated_at', '>=', now()->subDays(7))->count();

        return [
            'total_rates' => $total,
            'average_value' => round($avgValue ?? 0, 4),
            'highest_value' => $maxValue ?? 0,
            'lowest_value' => $minValue ?? 0,
            'recent_updates' => $recentUpdates,
            'update_percentage' => $total > 0 ? round(($recentUpdates / $total) * 100, 2) : 0,
            'value_range' => $maxValue && $minValue ? round($maxValue - $minValue, 4) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderedByValue(string $direction = 'desc'): Collection
    {
        return $this->model->with('currency')->orderBy('value', $direction)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getHighestRates(int $limit = 10): Collection
    {
        return $this->model->with('currency')
            ->orderBy('value', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getLowestRates(int $limit = 10): Collection
    {
        return $this->model->with('currency')
            ->orderBy('value', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function createBulkExchangeRates(array $exchangeRatesData): Collection
    {
        $rates = collect();

        foreach ($exchangeRatesData as $rateData) {
            $currencyId = $rateData['currency_id'];
            $value = $rateData['value'];

            // Verificar si ya existe antes de crear
            if (!$this->existsForCurrency($currencyId)) {
                $rate = $this->createExchangeRate($currencyId, $value);
                $rates->push($rate);
            }
        }

        return $rates;
    }

    /**
     * {@inheritDoc}
     */
    public function getForSelect(): array
    {
        $rates = $this->model->with('currency')->get();
        $result = [];

        foreach ($rates as $rate) {
            $currencyCode = $rate->currency?->code ?? 'Sin moneda';
            $value = number_format($rate->value, 4);
            $result[$rate->id] = "{$currencyCode} - {$value}";
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function validateExchangeRateData(array $data, ?string $excludeId = null): array
    {
        $errors = [];

        // Validar moneda requerida
        if (empty($data['currency_id'])) {
            $errors['currency_id'] = 'La moneda es requerida';
        }

        // Validar valor requerido
        if (!isset($data['value'])) {
            $errors['value'] = 'El valor es requerido';
        } elseif (!$this->isValidValue($data['value'])) {
            $errors['value'] = 'El valor debe ser mayor a 0 y menor a 999999.9999';
        }

        // Validar unicidad de moneda
        if (!empty($data['currency_id'])) {
            $existingQuery = $this->model->where('currency_id', $data['currency_id']);

            if ($excludeId) {
                $existingQuery->where('id', '!=', $excludeId);
            }

            if ($existingQuery->exists()) {
                $errors['currency_id'] = 'Ya existe un tipo de cambio para esta moneda';
            }
        }

        return $errors;
    }

    /**
     * {@inheritDoc}
     */
    public function getValueByCurrency(string $currencyId): ?float
    {
        return $this->model->where('currency_id', $currencyId)->value('value');
    }

    /**
     * {@inheritDoc}
     */
    public function bulkUpdateValues(array $updates): int
    {
        $updated = 0;

        foreach ($updates as $currencyId => $value) {
            if ($this->isValidValue($value) && $this->updateByCurrency($currencyId, $value)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentlyUpdated(int $days = 7): Collection
    {
        return $this->model->with('currency')
            ->where('updated_at', '>=', now()->subDays($days))
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function isValidValue(float $value): bool
    {
        return $value > 0 && $value <= 999999.9999;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrenciesWithoutExchangeRate(): Collection
    {
        return DB::table('currencies')
            ->leftJoin('exchange_rates', 'currencies.id', '=', 'exchange_rates.currency_id')
            ->whereNull('exchange_rates.currency_id')
            ->select('currencies.*')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function convertCurrency(string $fromCurrencyId, string $toCurrencyId, float $amount): ?float
    {
        $fromRate = $this->getValueByCurrency($fromCurrencyId);
        $toRate = $this->getValueByCurrency($toCurrencyId);

        if (!$fromRate || !$toRate || $fromRate <= 0 || $toRate <= 0) {
            return null;
        }

        // Conversión: (cantidad / tasa_origen) * tasa_destino
        return ($amount / $fromRate) * $toRate;
    }

    /**
     * {@inheritDoc}
     */
    public function createOrUpdateByCurrency(string $currencyId, float $value): Model
    {
        if (!$this->isValidValue($value)) {
            throw new \InvalidArgumentException('Valor de tipo de cambio inválido');
        }

        $existingRate = $this->getByCurrency($currencyId);

        if ($existingRate) {
            $this->updateValue($existingRate->id, $value);
            return $existingRate->fresh();
        }

        return $this->createExchangeRate($currencyId, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function fetch(): array
    {
        $response = Http::timeout(10)
            ->get('https://smcvenezuela.xyz/dolarsmcbot/binance-api.php')
            ->throw()
            ->json();

        return $response;
    }
}

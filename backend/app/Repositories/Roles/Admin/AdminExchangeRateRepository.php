<?php

namespace App\Repositories\Roles\Admin;

use App\Contracts\Roles\Admin\AdminExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Models\ExchangeRate;
use App\Repositories\ExchangeRate\RoleExchangeRateRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Repository para manejar operaciones de tasas de cambio de administradores
 */
class AdminExchangeRateRepository extends RoleExchangeRateRepository implements AdminExchangeRateRepositoryInterface
{
    /**
     * Constructor del repository
     *
     * @param ExchangeRate $model
     */
    public function __construct(ExchangeRate $model)
    {
        $this->model = $model;
    }

    /**
     * Implementación específica para admin: no usa foreign key de rol
     */
    public function createOrRestoreByCurrency(int $roleEntityId, ExchangeRateDTO $dto): Model
    {
        $data = [
            'currency_id' => $dto->currencyId,
            'amount'      => $dto->amount, // Use amount field as per migration
        ];

        $model = $this->model
            ->withTrashed()
            ->where('currency_id', $dto->currencyId)
            ->first();

        if ($model) {
            $model->trashed() && $model->restore();
            $model->update($data);
            return $model;
        }

        return $this->model->create($data);
    }

    /**
     * Obtiene todas las tasas de cambio
     *
     * @return Collection
     */
    public function getAllExchangeRates(): Collection
    {
        return $this->model->with('currency:id,iso_code,name,description,type,status,country_id')
            ->with('currency.country:id,name,iso_code_2,iso_code_3')
            ->orderBy('currency_id')
            ->get();
    }

    /**
     * Obtiene tasas de cambio por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getExchangeRatesByCurrency($currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)
            ->orderByDesc('updated_at')
            ->get(['currency_id', 'amount', 'updated_at']);
    }

    /**
     * Obtiene valor específico de tasa por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getCurrencyValue($currencyId, string $field = 'amount')
    {
        return $this->model->where('currency_id', $currencyId)
            ->orderByDesc('updated_at')
            ->value($field);
    }

    /**
     * Crea o actualiza una tasa de cambio
     *
     * @param mixed $currencyId ID de la moneda
     * @param float $amount Valor de la tasa
     * @param float $percentage Porcentaje
     * @return bool
     */
    public function updateOrCreateExchangeRate($currencyId, float $amount, $percentage): bool
    {
        try {
            $data = [
                'currency_id' => $currencyId,
                'amount'      => $amount,
                'percentage'  => $percentage,
                'user_id'     => Auth::id()
            ];

            $this->model->updateOrCreate(
                ['currency_id' => $currencyId],
                $data
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene la tasa más reciente por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return ExchangeRate|null
     */
    public function getLatestRate($currencyId): ?ExchangeRate
    {
        return $this->model->where('currency_id', $currencyId)
            ->orderByDesc('updated_at')
            ->first();
    }

    /**
     * Obtiene el historial completo de tasas por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getHistoryByCurrency($currencyId): Collection
    {
        return $this->model
            ->with(['currency', 'user:id,first_name,last_name'])
            ->where('currency_id', $currencyId)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($rate) {
                $rate->currency_name = $rate->currency?->name ?? '-';
                $rate->user_name = $rate->user ? trim("{$rate->user->first_name} {$rate->user->last_name}") : '-';
                return $rate;
            });
    }

    /**
     * Obtiene las tasas de cambio disponibles para administradores
     *
     * @return Collection
     */
    public function getAvailableExchangeRates(): Collection
    {
        return $this->model->where('status', '<>', 'OLD')->get();
    }

    /**
     * Obtiene los códigos ISO de las monedas disponibles
     *
     * @return array Lista de códigos ISO
     */
    public function getAvailableCurrenciesIsoCodes(): array
    {
        return $this->model
            ->join('currencies', 'currencies.id', '=', 'exchange_rates.currency_id')
            ->where('currencies.status', 'ACTIVE')
            ->distinct()
            ->pluck('currencies.iso_code')
            ->toArray();
    }
}

<?php

namespace App\Repositories\Users\Roles\Commerce;

use App\Models\Users\Roles\Commerce\CommerceExchangeRate;
use App\Repositories\BaseRepository;
use App\Repositories\Users\Roles\Commerce\Contracts\CommerceExchangeRateRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de tasas de cambio de comercios
 */
class CommerceExchangeRateRepository extends BaseRepository implements CommerceExchangeRateRepositoryContract
{
    /**
     * Constructor del repository
     *
     * @param CommerceExchangeRate $model
     */
    public function __construct(CommerceExchangeRate $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtiene todas las tasas de cambio por moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getAllExchangeRatesByCurrency($commerceId, $currencyId): Collection
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->get(['currency_id', 'exchange_rate', 'percentage']);
    }

    /**
     * Obtiene valor específico de tasa de comercio y moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getCommerceCurrencyValue($commerceId, $currencyId, string $field = 'exchange_rate')
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->value($field);
    }

    /**
     * Obtiene todas las tasas de cambio de un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return Collection
     */
    public function getAllExchangeRates($commerceId): Collection
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->get(['currency_id', 'exchange_rate']);
    }

    /**
     * Obtiene valores de tasas por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getValuesByCurrency($currencyId): Collection
    {
        return $this->model->select('exchange_rate')
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->get();
    }

    /**
     * Obtiene tasas de cambio disponibles
     *
     * @param mixed $commerceId ID del comercio
     * @return Collection
     */
    public function getAvailableExchangeRates($commerceId): Collection
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('status', '<>', 'OLD')
            ->get();
    }

    /**
     * Obtiene monedas por comercio
     *
     * @param mixed $commerceId ID del comercio
     * @param string|null $status Estado de las tasas
     * @return array
     */
    public function getCurrenciesByCommerce($commerceId, ?string $status = 'ACTIVE'): array
    {
        $query = $this->model->select('currency_id')
            ->where('commerce_id', $commerceId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->distinct()->pluck('currency_id')->toArray();
    }

    /**
     * Actualiza o crea nueva tasa de cambio
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     * @param float $amount Monto de la tasa
     * @param float $percentage Porcentaje
     * @return bool
     */
    public function updateOrCreateExchangeRate($commerceId, $currencyId, float $amount, float $percentage = 0): bool
    {
        try {
            $lastRate = $this->model->where('commerce_id', $commerceId)
                ->where('currency_id', $currencyId)
                ->where('status', 'ACTIVE')
                ->orderByDesc('id')
                ->first();

            // Solo crear nuevo si no existe o si el valor cambió
            if (!$lastRate || $lastRate->exchange_rate != $amount) {
                // Desactivar la tasa anterior si existe
                if ($lastRate) {
                    $lastRate->update(['status' => 'OLD']);
                }

                $status = $amount > 0 ? 'ACTIVE' : 'INACTIVE';

                // Crear nueva tasa activa
                $this->model->create([
                    'commerce_id' => $commerceId,
                    'currency_id' => $currencyId,
                    'exchange_rate' => $amount,
                    'percentage' => $percentage,
                    'status' => $status
                ]);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene registros por comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return Collection
     */
    public function getByCommerce($commerceId): Collection
    {
        return $this->model->where('commerce_id', $commerceId)->get();
    }

    /**
     * Obtiene registros por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getByCurrency($currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)->get();
    }

    /**
     * Obtiene registros por estado
     *
     * @param string $status Estado de la tasa
     * @return Collection
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Obtiene tasas activas
     *
     * @return Collection
     */
    public function getActiveRates(): Collection
    {
        return $this->model->where('status', 'ACTIVE')->get();
    }

    /**
     * Obtiene tasas inactivas
     *
     * @return Collection
     */
    public function getInactiveRates(): Collection
    {
        return $this->model->where('status', 'INACTIVE')->get();
    }

    /**
     * Obtiene tasas obsoletas
     *
     * @return Collection
     */
    public function getOldRates(): Collection
    {
        return $this->model->where('status', 'OLD')->get();
    }

    /**
     * Actualiza estado de una tasa
     *
     * @param mixed $id ID de la tasa
     * @param string $status Nuevo estado
     * @return bool
     */
    public function updateStatus($id, string $status): bool
    {
        return $this->model->where('id', $id)
            ->update(['status' => $status]) > 0;
    }

    /**
     * Marca tasas como obsoletas por comercio y moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     * @return int Número de registros actualizados
     */
    public function markAsOld($commerceId, $currencyId): int
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->update(['status' => 'OLD']);
    }

    /**
     * Obtiene la tasa más reciente por comercio y moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     * @return CommerceExchangeRate|null
     */
    public function getLatestRate($commerceId, $currencyId): ?CommerceExchangeRate
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->first();
    }
}
<?php

namespace App\Repositories\Roles\Commerce;

use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Models\Roles\Commerce\CommerceExchangeRate;
use App\Repositories\ExchangeRate\RoleExchangeRateRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de tasas de cambio de comercios
 */
class CommerceExchangeRateRepository extends RoleExchangeRateRepository implements CommerceExchangeRateRepositoryInterface
{
    /**
     * Constructor del repository
     */
    public function __construct(CommerceExchangeRate $model)
    {
        $this->model = $model;
    }

    /**
     * Obtiene tasas activas
     */
    public function getActiveRates() : Collection
    {
        return $this->model->where('status', 'ACTIVE')->get();
    }

    /**
     * Obtiene todas las tasas de cambio de un comercio
     *
     * @param mixed $commerceId ID del comercio
     */
    public function getAllExchangeRates($commerceId) : Collection
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->get(['currency_id', 'amount']);
    }

    /**
     * Obtiene todas las tasas de cambio por moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     */
    public function getAllExchangeRatesByCurrency($commerceId, $currencyId) : Collection
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->get(['currency_id', 'amount', 'percentage']);
    }

    /**
     * Obtiene tasas de cambio disponibles
     *
     * @param mixed $commerceId ID del comercio
     */
    public function getAvailableExchangeRates($commerceId) : Collection
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('status', '<>', 'OLD')
            ->get();
    }

    /**
     * Obtiene registros por comercio
     *
     * @param mixed $commerceId ID del comercio
     */
    public function getByCommerce($commerceId) : Collection
    {
        return $this->model->where('commerce_id', $commerceId)->get();
    }

    /**
     * Obtiene valor específico de tasa de comercio y moneda
     *
     * @param mixed  $commerceId ID del comercio
     * @param mixed  $currencyId ID de la moneda
     * @param string $field      Campo a obtener
     *
     * @return mixed
     */
    public function getCommerceCurrencyValue($commerceId, $currencyId, string $field = 'amount')
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->value($field);
    }

    /**
     * Obtiene monedas por comercio
     *
     * @param mixed       $commerceId ID del comercio
     * @param string|null $status     Estado de las tasas
     */
    public function getCurrenciesByCommerce($commerceId, ?string $status = 'ACTIVE') : array
    {
        $query = $this->model->select('currency_id')
            ->where('commerce_id', $commerceId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->distinct()->pluck('currency_id')->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentRatesBatch($commerceId, array $currencyIds) : array
    {
        if (empty($currencyIds)) {
            return [];
        }

        // Una sola query trae TODAS las filas ACTIVE candidatas (ordenadas
        // por id desc); nos quedamos con la primera ocurrencia por
        // currency_id en PHP — mismo criterio que getCommerceCurrencyValue()
        // pero para todas las monedas del lote a la vez.
        $rows = $this->model
            ->where('commerce_id', $commerceId)
            ->whereIn('currency_id', $currencyIds)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->get(['currency_id', 'amount']);

        $result = [];
        foreach ($rows as $row) {
            $key = (string) $row->currency_id;
            if (! isset($result[$key])) {
                $result[$key] = (float) $row->amount;
            }
        }

        return $result;
    }

    /**
     * Obtiene el historial completo de tasas por comercio y moneda.
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     */
    public function getHistoryByCurrency($commerceId, $currencyId) : Collection
    {
        return $this->model
            ->with('currency')
            ->where('commerce_id', $commerceId)
            ->where('currency_id', $currencyId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Obtiene tasas inactivas
     */
    public function getInactiveRates() : Collection
    {
        return $this->model->where('status', 'INACTIVE')->get();
    }

    /**
     * Obtiene la tasa más reciente por comercio y moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     */
    public function getLatestRate($commerceId, $currencyId) : ?CommerceExchangeRate
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Obtiene tasas obsoletas
     */
    public function getOldRates() : Collection
    {
        return $this->model->where('status', 'OLD')->get();
    }

    /**
     * Obtiene valores de tasas por moneda
     *
     * @param mixed $currencyId ID de la moneda
     */
    public function getValuesByCurrency($currencyId) : Collection
    {
        return $this->model->select('amount')
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->get();
    }

    /**
     * Marca tasas como obsoletas por comercio y moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     *
     * @return int Número de registros actualizados
     */
    public function markAsOld($commerceId, $currencyId) : int
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->update(['status' => 'OLD']);
    }

    /**
     * Actualiza o crea nueva tasa de cambio
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $currencyId ID de la moneda
     * @param float $amount     Monto de la tasa
     * @param float $percentage Porcentaje
     */
    public function updateOrCreateExchangeRate($commerceId, $currencyId, float $amount, float $percentage = 0) : bool
    {
        try {
            $lastRate = $this->model->where('commerce_id', $commerceId)
                ->where('currency_id', $currencyId)
                ->where('status', 'ACTIVE')
                ->orderByDesc('id')
                ->first();

            // Solo crear nuevo si no existe o si el valor cambió
            if (! $lastRate || $lastRate->amount != $amount) {
                // Desactivar la tasa anterior si existe
                if ($lastRate) {
                    $lastRate->update(['status' => 'OLD']);
                }

                $status = $amount > 0 ? 'ACTIVE' : 'INACTIVE';

                // Crear nueva tasa activa
                $this->model->create([
                    'commerce_id' => $commerceId,
                    'currency_id' => $currencyId,
                    'amount' => $amount,
                    'percentage' => $percentage,
                    'status' => $status,
                ]);
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Actualiza estado de una tasa
     *
     * @param mixed  $id     ID de la tasa
     * @param string $status Nuevo estado
     */
    public function updateStatus($id, string $status) : bool
    {
        return $this->model->where('id', $id)
            ->update(['status' => $status]) > 0;
    }
}

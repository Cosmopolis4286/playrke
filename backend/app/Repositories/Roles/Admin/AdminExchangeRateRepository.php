<?php

namespace App\Repositories\Roles\Admin;

use App\Contracts\Roles\Admin\AdminExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Models\ExchangeRate;
use App\Repositories\ExchangeRate\RoleExchangeRateRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

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
        return $this->model->with('currency:id,iso_code,name')
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
     * @param float $value Valor de la tasa
     * @return bool
     */
    public function updateOrCreateExchangeRate($currencyId, float $value): bool
    {
        try {
            $this->model->updateOrCreate(
                ['currency_id' => $currencyId],
                ['value' => $value]
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
            ->with('currency')
            ->where('currency_id', $currencyId)
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Implementación requerida por la interfaz base
     * Obtiene tasas disponibles (para admin, todas las tasas)
     *
     * @return Collection
     */
    public function getAvailableExchangeRates($entityId = null): Collection
    {
        return $this->getAllExchangeRates();
    }

    /**
     * Find exchange rate with relations
     */
    public function findWithRelations(array $relations, string $id, array $columns = ['*']): ?ExchangeRate
    {
        return $this->model->with($relations)->select($columns)->find($id);
    }

    /**
     * Check if exchange rate exists
     */
    public function exists(string $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Find by ID or throw exception
     */
    public function findOrFail(string|int $id, array $columns = ['*']): ExchangeRate
    {
        return $this->model->findOrFail($id, $columns);
    }

    /**
     * Update exchange rate
     */
    public function update(string|int $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    /**
     * Delete exchange rate
     */
    public function delete(string $id): bool
    {
        return $this->model->where('id', $id)->delete() > 0;
    }

    /**
     * Toggle status (not applicable for admin, but implementing for interface compatibility)
     */
    public function toggleStatus(ExchangeRate $exchangeRate): bool
    {
        // Admin exchange rates don't have status, but we implement for compatibility
        return true;
    }
}

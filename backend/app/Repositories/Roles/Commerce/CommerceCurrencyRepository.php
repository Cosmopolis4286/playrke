<?php

namespace App\Repositories\Roles\Commerce;

use App\Contracts\Roles\Commerce\CommerceCurrencyRepositoryInterface;
use App\Models\Pivots\Roles\Commerce\CommerceCurrency;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de países y monedas de comercios
 */
class CommerceCurrencyRepository extends BaseRepository implements CommerceCurrencyRepositoryInterface
{
    /**
     * Constructor del repository
     *
     * @param CommerceCurrency $model
     */
    public function __construct(CommerceCurrency $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtiene monedas disponibles para un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return array
     */
    public function getAvailableCurrencies($commerceId): array
    {
        return $this->model->select('currency_id')
            ->where('commerce_id', $commerceId)
            ->where('status', 'ACTIVE')
            ->distinct()->pluck('currency_id')->toArray();
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
     * Obtiene registros por nivel
     *
     * @param int $level Nivel de asignación
     * @return Collection
     */
    public function getByLevel(int $level): Collection
    {
        return $this->model->where('level', $level)->get();
    }

    /**
     * Obtiene registros activos
     *
     * @return Collection
     */
    public function getActiveRecords(): Collection
    {
        return $this->model->where('status', 'ACTIVE')->get();
    }

    /**
     * Obtiene registros inactivos
     *
     * @return Collection
     */
    public function getInactiveRecords(): Collection
    {
        return $this->model->where('status', 'INACTIVE')->get();
    }

    /**
     * Actualiza estado de un registro
     *
     * @param mixed $id ID del registro
     * @param string $status Nuevo estado
     * @return bool
     */
    public function updateStatus($id, string $status): bool
    {
        return $this->model->where('id', $id)
            ->update(['status' => $status]) > 0;
    }

    /**
     * Actualiza nivel de un registro
     *
     * @param mixed $id ID del registro
     * @param int $level Nuevo nivel
     * @return bool
     */
    public function updateLevel($id, int $level): bool
    {
        return $this->model->where('id', $id)
            ->update(['level' => $level]) > 0;
    }

    /**
     * Activa registros de un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return int Número de registros actualizados
     */
    public function activateByCommerce($commerceId): int
    {
        return $this->model->where('commerce_id', $commerceId)
            ->update(['status' => 'ACTIVE']);
    }

    /**
     * Desactiva registros de un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return int Número de registros actualizados
     */
    public function deactivateByCommerce($commerceId): int
    {
        return $this->model->where('commerce_id', $commerceId)
            ->update(['status' => 'INACTIVE']);
    }
}

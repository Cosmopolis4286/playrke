<?php

namespace App\Repositories\Users\Roles\Commerce;

use App\Models\Users\Commerce\CommerceCurrency;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Users\Roles\Commerce\Contracts\CommerceCurrencyRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de países y monedas de comercios
 */
class CommerceCurrencyRepository extends BaseRepository implements CommerceCurrencyRepositoryContract
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
     * @param mixed|null $exceptCurrencyId ID de moneda a excluir
     * @return array
     */
    public function getAvailableCurrencies($commerceId, $exceptCurrencyId = null): array
    {
        $query = $this->model->select('currency_id')
            ->where('commerce_id', $commerceId)
            ->where('status', 'ACTIVE');

        if ($exceptCurrencyId !== null) {
            $query->where('currency_id', '<>', $exceptCurrencyId);
        }

        return $query->distinct()->pluck('currency_id')->toArray();
    }

    /**
     * Obtiene todos los países y monedas disponibles para un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @param array $fields Campos a seleccionar
     * @return Collection
     */
    public function getAllAvailableCountriesCurrencies($commerceId, array $fields = ['*']): Collection
    {
        return $this->model->select($fields)
            ->where('commerce_id', $commerceId)
            ->where('status', 'ACTIVE')
            ->orderBy('country_id')
            ->get();
    }

    /**
     * Obtiene países disponibles para un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return array
     */
    public function getAvailableCountries($commerceId): array
    {
        return $this->model->select('country_id')
            ->where('commerce_id', $commerceId)
            ->where('status', 'ACTIVE')
            ->distinct()
            ->pluck('country_id')
            ->toArray();
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
     * Obtiene registros por país
     *
     * @param mixed $countryId ID del país
     * @return Collection
     */
    public function getByCountry($countryId): Collection
    {
        return $this->model->where('country_id', $countryId)->get();
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
     * Obtiene registros por usuario creador
     *
     * @param mixed $creatorUserId ID del usuario creador
     * @return Collection
     */
    public function getByCreatorUser($creatorUserId): Collection
    {
        return $this->model->where('creator_user_id', $creatorUserId)->get();
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
     * Verifica si existe combinación comercio-país-moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $countryId ID del país
     * @param mixed $currencyId ID de la moneda
     * @return bool
     */
    public function existsCombination($commerceId, $countryId, $currencyId): bool
    {
        return $this->model->where('commerce_id', $commerceId)
            ->where('country_id', $countryId)
            ->where('currency_id', $currencyId)
            ->exists();
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

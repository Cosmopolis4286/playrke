<?php

namespace App\Repositories\Roles\Commerce;

use App\Repositories\BaseRepository;
use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Models\Roles\Commerce;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de comercios
 */
class CommerceRepository extends BaseRepository implements CommerceRepositoryInterface
{
    /**
     * Constructor del repository
     *
     * @param Commerce $model
     */
    public function __construct(Commerce $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtiene comercio por ID de usuario
     *
     * @param mixed $userId ID del usuario
     * @param array $fields Campos a seleccionar
     * @return Commerce|null
     */
    public function getByUserId($userId, array $fields = ['*']): ?Commerce
    {
        return $this->model->select($fields)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Obtiene valor por ID de usuario
     *
     * @param mixed $userId ID del usuario
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueByUserId($userId, string $field)
    {
        return $this->model->where('user_id', $userId)->value($field);
    }

    /**
     * Obtiene comercio por código UUID
     *
     * @param string $code Código UUID
     * @param array $fields Campos a seleccionar
     * @return Commerce|null
     */
    public function getByCodeUUID(string $code, array $fields = ['*']): ?Commerce
    {
        return $this->model->select($fields)
            ->where('unique_code_id', $code)
            ->first();
    }

    /**
     * Obtiene valor por código UUID
     *
     * @param string $code Código UUID
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueByCodeUUID(string $code, string $field)
    {
        return $this->model->where('unique_code_id', $code)->value($field);
    }

    /**
     * Verifica si existe un valor en una columna
     *
     * @param string $column Columna a verificar
     * @param mixed $value Valor a buscar
     * @return bool
     */
    public function existsValue(string $column, $value): bool
    {
        return $this->model->where($column, $value)->exists();
    }

    /**
     * Obtiene comercios por país
     *
     * @param mixed $countryId ID del país
     * @return Collection
     */
    public function getByCountry($countryId): Collection
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    /**
     * Obtiene comercios por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getByCurrency($currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)->get();
    }

    /**
     * Obtiene comercios inactivos
     *
     * @return Collection
     */
    public function getInactiveCommerces(): Collection
    {
        return $this->model->where('status', 'INACTIVE')->get();
    }

    /**
     * Obtiene comercios bloqueados
     *
     * @return Collection
     */
    public function getBlockedCommerces(): Collection
    {
        return $this->model->where('status', 'BLOCKED')->get();
    }

    /**
     * Actualiza estado de un comercio
     *
     * @param mixed $id ID del comercio
     * @param string $status Nuevo estado
     * @return bool
     */
    public function updateStatus($id, string $status): bool
    {
        return $this->model->where('id', $id)
            ->update(['status' => $status]) > 0;
    }

    /**
     * Activa un comercio
     *
     * @param mixed $id ID del comercio
     * @return bool
     */
    public function activate($id): bool
    {
        return $this->updateStatus($id, 'ACTIVE');
    }

    /**
     * Desactiva un comercio
     *
     * @param mixed $id ID del comercio
     * @return bool
     */
    public function deactivate($id): bool
    {
        return $this->updateStatus($id, 'INACTIVE');
    }

    /**
     * Bloquea un comercio
     *
     * @param mixed $id ID del comercio
     * @return bool
     */
    public function block($id): bool
    {
        return $this->updateStatus($id, 'BLOCKED');
    }

    /**
     * Actualiza comisión de un comercio
     *
     * @param mixed $id ID del comercio
     * @param float $commission Nueva comisión
     * @return bool
     */
    public function updateCommission($id, float $commission): bool
    {
        return $this->model->where('id', $id)
            ->update(['commission' => $commission]) > 0;
    }
}

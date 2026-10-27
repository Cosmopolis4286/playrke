<?php

namespace App\Repositories\Roles\Commerce;

use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Models\Roles\Commerce;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de comercios
 */
class CommerceRepository extends BaseRepository implements CommerceRepositoryInterface
{
    /**
     * Constructor del repository
     */
    public function __construct(Commerce $model)
    {
        $this->model = $model;
    }

    /**
     * Activa un comercio
     *
     * @param mixed $id ID del comercio
     */
    public function activate($id) : bool
    {
        return $this->updateStatus($id, 'ACTIVE');
    }

    /**
     * Bloquea un comercio
     *
     * @param mixed $id ID del comercio
     */
    public function block($id) : bool
    {
        return $this->updateStatus($id, 'BLOCKED');
    }

    /**
     * Desactiva un comercio
     *
     * @param mixed $id ID del comercio
     */
    public function deactivate($id) : bool
    {
        return $this->updateStatus($id, 'INACTIVE');
    }

    /**
     * Verifica si existe un valor en una columna
     *
     * @param string $column Columna a verificar
     * @param mixed  $value  Valor a buscar
     */
    public function existsValue(string $column, $value) : bool
    {
        return $this->model->where($column, $value)->exists();
    }

    /**
     * Obtiene comercios bloqueados
     */
    public function getBlockedRecords() : Collection
    {
        return $this->model->where('status', 'BLOCKED')->get();
    }

    /**
     * Obtiene comercio por código UUID
     *
     * @param string $code   Código UUID
     * @param array  $fields Campos a seleccionar
     */
    public function getByCodeUUID(string $code, array $fields = ['*']) : ?Commerce
    {
        return $this->model->select($fields)
            ->where('unique_code_id', $code)
            ->first();
    }

    /**
     * Obtiene comercios por país
     *
     * @param mixed $countryId ID del país
     */
    public function getByCountry($countryId) : Collection
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    /**
     * Obtiene comercios que manejan una moneda dada (relación many-to-many
     * vía pivote commerces_currencies — no existe columna currency_id en
     * commerces).
     *
     * @param mixed $currencyId ID de la moneda
     */
    public function getByCurrency($currencyId) : Collection
    {
        return $this->model
            ->whereHas('currencies', fn ($q) => $q->where('currencies.id', $currencyId))
            ->get();
    }

    /**
     * Obtiene comercio por ID de usuario
     *
     * @param mixed $userId ID del usuario
     * @param array $fields Campos a seleccionar
     */
    public function getByUserId($userId, array $fields = ['*']) : ?Commerce
    {
        return $this->model->select($fields)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Obtiene comercios inactivos
     */
    public function getInactiveRecords() : Collection
    {
        return $this->model->where('status', 'INACTIVE')->get();
    }

    /**
     * Obtiene valor por código UUID
     *
     * @param string $code  Código UUID
     * @param string $field Campo a obtener
     *
     * @return mixed
     */
    public function getValueByCodeUUID(string $code, string $field)
    {
        return $this->model->where('unique_code_id', $code)->value($field);
    }

    /**
     * Actualiza comisión de un comercio
     *
     * @param mixed $id         ID del comercio
     * @param float $commission Nueva comisión
     */
    public function updateCommission($id, float $commission) : bool
    {
        return $this->model->where('id', $id)
            ->update(['commission' => $commission]) > 0;
    }

    /**
     * Actualiza estado de un comercio
     *
     * @param mixed  $id     ID del comercio
     * @param string $status Nuevo estado
     */
    public function updateStatus($id, string $status) : bool
    {
        return $this->model->where('id', $id)
            ->update(['status' => $status]) > 0;
    }
}

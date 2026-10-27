<?php

namespace App\Repositories\Roles\Admin;

use App\Contracts\Roles\Admin\AdminRepositoryInterface;
use App\Models\Roles\Admin;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de administradores
 */
class AdminRepository extends BaseRepository implements AdminRepositoryInterface
{
    /**
     * Constructor del repository
     */
    public function __construct(Admin $model)
    {
        $this->model = $model;
    }

    /**
     * Activa un administrador
     *
     * @param mixed $id ID del administrador
     */
    public function activate($id) : bool
    {
        return $this->updateStatus($id, 'ACTIVE');
    }

    /**
     * Bloquea un administrador
     *
     * @param mixed $id ID del administrador
     */
    public function block($id) : bool
    {
        return $this->updateStatus($id, 'BLOCKED');
    }

    /**
     * Desactiva un administrador
     *
     * @param mixed $id ID del administrador
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
     * Obtiene administradores bloqueados
     */
    public function getBlockedRecords() : Collection
    {
        return $this->model->where('status', 'BLOCKED')->get();
    }

    /**
     * Obtiene administrador por código UUID
     *
     * @param string $code   Código UUID
     * @param array  $fields Campos a seleccionar
     */
    public function getByCodeUUID(string $code, array $fields = ['*']) : ?Admin
    {
        return $this->model->select($fields)
            ->where('unique_code_id', $code)
            ->first();
    }

    /**
     * Obtiene administradores por país
     *
     * @param mixed $countryId ID del país
     */
    public function getByCountry($countryId) : Collection
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    /**
     * Obtiene administrador por ID de usuario
     *
     * @param mixed $userId ID del usuario
     * @param array $fields Campos a seleccionar
     */
    public function getByUserId($userId, array $fields = ['*']) : ?Admin
    {
        return $this->model->select($fields)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Obtiene administradores inactivos
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
     * Actualiza estado de un administrador
     *
     * @param mixed  $id     ID del administrador
     * @param string $status Nuevo estado
     */
    public function updateStatus($id, string $status) : bool
    {
        return $this->model->where('id', $id)
            ->update(['status' => $status]) > 0;
    }
}

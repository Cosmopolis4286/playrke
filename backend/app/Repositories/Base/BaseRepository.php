<?php

namespace App\Repositories\Base;

use App\Repositories\Base\Contracts\BaseRepositoryContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositorio base que implementa funcionalidad común
 * Todos los repositorios específicos deben extender esta clase
 */
abstract class BaseRepository implements BaseRepositoryContract
{
    /**
     * Instancia del modelo Eloquent
     */
    protected Model $model;

    /**
     * Constructor del repositorio base
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Obtener todos los registros
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->get($columns);
    }

    /**
     * Buscar un registro por ID
     *
     * @param string|int $id ID del registro
     * @param array $columns Columnas a seleccionar
     * @return Model|null
     */
    public function find(string|int $id, array $columns = ['*']): ?Model
    {
        return $this->model->find($id, $columns);
    }

    /**
     * Buscar un registro por ID o lanzar excepción
     *
     * @param string|int $id ID del registro
     * @param array $columns Columnas a seleccionar
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string|int $id, array $columns = ['*']): Model
    {
        return $this->model->findOrFail($id, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function getValueById(string|int $id, string $field): mixed
    {
        return $this->model->where('id', $id)->value($field);
    }

    /**
     * {@inheritDoc}
     */
    public function existsByField(string $field, mixed $value): bool
    {
        return $this->model
            ->newQuery()
            ->where($field, $value)
            ->exists();
    }

    /**
     * Crear un nuevo registro
     *
     * @param array $data Datos para crear el registro
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Actualizar un registro existente
     *
     * @param string|int $id ID del registro a actualizar
     * @param array $data Datos para actualizar
     * @return Model
     */
    public function update(string|int $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    /**
     * Eliminar un registro
     *
     * @param string|int $id ID del registro a eliminar
     * @return bool
     */
    public function delete(string|int $id): bool
    {
        return (bool) $this->model->destroy($id);
    }

    /**
     * Buscar registros con condiciones
     *
     * @param array $conditions Condiciones de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Collection
     */
    public function where(array $conditions, array $columns = ['*']): Collection
    {
        $query = $this->model->query();

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->get($columns);
    }

    /**
     * Paginar resultados
     *
     * @param int $perPage Registros por página
     * @param array $columns Columnas a seleccionar
     * @param int $page Página actual
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], int $page = 1): LengthAwarePaginator
    {
        return $this->model->paginate($perPage, $columns, 'page', $page);
    }

    /**
     * Verificar si existe un registro
     *
     * @param string|int $id ID del registro
     * @return bool
     */
    public function exists(string|int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Contar registros
     *
     * @param array $conditions Condiciones de conteo
     * @return int
     */
    public function count(array $conditions = []): int
    {
        $query = $this->model->query();

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->count();
    }

    /**
     * Buscar primer registro que coincida
     *
     * @param array $conditions Condiciones de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Model|null
     */
    public function first(array $conditions, array $columns = ['*']): ?Model
    {
        $query = $this->model->query();

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->first($columns);
    }

    /**
     * Resetear modelo para nueva consulta
     *
     * @return void
     */
    protected function resetModel(): void
    {
        $this->model = $this->model->newInstance();
    }

    /**
     * Obtener instancia fresca del modelo
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Obtiene todos los registros con relaciones cargadas.
     *
     * @param array $relations Relaciones Eloquent a cargar (ej: ['country:id,name', 'user:id,email'])
     * @param array $fields Campos a seleccionar en la tabla principal
     *
     * @return Collection
     */
    public function getAllWithRelations(array $relations = [], array $fields = ['*']): Collection
    {
        return $this->model
            ->select($fields)
            ->with($relations)
            ->get();
    }
}

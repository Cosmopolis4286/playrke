<?php

namespace App\Repositories\Base;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositorio base que implementa funcionalidad común
 * Todos los repositorios específicos deben extender esta clase
 */
abstract class BaseRepository implements BaseRepositoryInterface
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
     * {@inheritDoc}
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->get($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function find(string|int $id, array $columns = ['*']): ?Model
    {
        return $this->model->find($id, $columns);
    }

    /**
     * {@inheritDoc}
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
    public function getByIds(array $Ids, array $fields = ['*']): Collection
    {
        return $this->model->whereIn('id', $Ids)->get($fields);
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
     * {@inheritDoc}
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(string|int $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string|int $id): bool
    {
        return (bool) $this->model->destroy($id);
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], int $page = 1): LengthAwarePaginator
    {
        return $this->model->paginate($perPage, $columns, 'page', $page);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string|int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function resetModel(): void
    {
        $this->model = $this->model->newInstance();
    }

    /**
     * {@inheritDoc}
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllWithRelations(array $relations = [], array $fields = ['*']): Collection
    {
        return $this->model
            ->select($fields)
            ->with($relations)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByStatus(string $status,  array $fields = ['*']): Collection
    {
        return $this->model->where('status', $status)->get($fields);
    }
}

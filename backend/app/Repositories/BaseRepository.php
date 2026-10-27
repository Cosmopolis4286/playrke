<?php

namespace App\Repositories;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Common\Status\StatusChanger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

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
     * Instancia interna de StatusChanger
     */
    protected ?StatusChanger $statusChanger = null;

    /**
     * {@inheritDoc}
     */
    public function all(array $columns = ['*']) : Collection
    {
        return $this->model->get($columns);
    }

    public function changeStatus(
        Model $model,
        ?string $newStatus = null,
        ?string $userRelation = null,
    ) : bool {
        $status = $newStatus
            ?? ($model->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE');

        try {
            DB::transaction(function() use ($model, $status, $userRelation) {
                $model->update(['status' => $status]);

                if ($userRelation) {
                    $model->loadMissing($userRelation);

                    if ($model->$userRelation instanceof Model) {
                        $model->$userRelation->update(['status' => $status]);
                    }
                }
            });

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function count(array $conditions = []) : int
    {
        $query = $this->model->newQuery();

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
    public function create(array $data) : Model
    {
        return $this->model->create($data);
    }

    /**
     * Elimina un registro por su ID y actualiza su estado a "DELETED".
     *
     * Primero actualiza el campo `status` a "DELETED" y luego realiza la eliminación física.
     *
     * @param string|int $id ID del registro a eliminar
     *
     * @return bool indica si la eliminación fue exitosa
     */
    public function delete(string|int $id) : bool
    {
        $model = $this->find($id);

        if (! $model) {
            return false;
        }

        $model->update(['status' => 'DELETED']);

        return (bool) $model->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string|int $id) : bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function existsByField(string $field, mixed $value) : bool
    {
        return $this->model
            ->newQuery()
            ->where($field, $value)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function find(string|int $id, array $columns = ['*']) : ?Model
    {
        return $this->model->find($id, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(string|int $id, array $columns = ['*']) : Model
    {
        return $this->model->findOrFail($id, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function findWithRelations(
        array $relations,
        string|int $id,
        array $fields = ['*'],
    ) : ?Model {
        return $this->model
            ->newQuery()
            ->select($fields)
            ->with($relations)
            ->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function first(array $conditions, array $columns = ['*']) : ?Model
    {
        $query = $this->model->newQuery();

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
    public function getAllWithRelations(array $relations = [], array $fields = ['*']) : Collection
    {
        return $this->model
            ->select($fields)
            ->with($relations)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByIds(array $ids, array $fields = ['*']) : Collection
    {
        return $this->model->whereIn('id', $ids)->get($fields);
    }

    /**
     * {@inheritDoc}
     */
    public function getByIdsWithRelations(array $relations, array $ids, array $fields = ['*']) : Collection
    {
        return $this->model->with($relations)->whereIn('id', $ids)->get($fields);
    }

    /**
     * {@inheritDoc}
     */
    public function getByStatus(string $status, array $fields = ['*']) : Collection
    {
        return $this->model->where('status', $status)->get($fields);
    }

    /**
     * {@inheritDoc}
     */
    public function getModel() : Model
    {
        return $this->model;
    }

    /**
     * {@inheritDoc}
     */
    public function getValueById(string|int $id, string $field) : mixed
    {
        return $this->model->where('id', $id)->value($field);
    }

    /**
     * {@inheritDoc}
     */
    public function getValueByUserId(string|int $userId, string $field) : mixed
    {
        return $this->model->where('user_id', $userId)->value($field);
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], int $page = 1) : LengthAwarePaginator
    {
        return $this->model->paginate($perPage, $columns, 'page', $page);
    }

    /**
     * {@inheritDoc}
     */
    public function paginateWithRelations(
        int $perPage = 15,
        int $page = 1,
        array $relations = [],
        array $conditions = [],
        array $fields = ['*'],
    ) : LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Seleccionar columnas específicas
        $query->select($fields);

        // Cargar relaciones
        if (! empty($relations)) {
            $query->with($relations);
        }

        // Aplicar condiciones where (AND)
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                // Por ejemplo ['field' => ['operator', 'value']]
                [$operator, $val] = $value;
                $query->where($field, $operator, $val);
            } else {
                $query->where($field, $value);
            }
        }

        // Retornar paginación, Laravel manejará página actual automáticamente si está en request
        return $query->paginate(
            $perPage,
            ['*'],
            'page',
            $page,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function query() : Builder
    {
        return $this->getModel()->newQuery();
    }

    /**
     * {@inheritDoc}
     */
    public function resetModel() : void
    {
        $this->model = $this->model->newInstance();
    }

    /**
     * Asigna una instancia de StatusChanger al repositorio.
     *
     * Permite inyectar la dependencia para gestionar cambios de estado
     * de los modelos manejados por el repositorio.
     *
     * @param StatusChanger $statusChanger instancia del servicio para cambiar estados
     */
    public function setStatusChanger(StatusChanger $statusChanger) : void
    {
        $this->statusChanger = $statusChanger;
    }

    /**
     * {@inheritDoc}
     */
    public function softDelete(
        int|string|Model $model,
        ?string $status = 'DELETED',
        array $relationsToDetach = [],
        ?string $userRelation = null,
    ) : bool {
        try {
            DB::transaction(function() use ($model, $status, $relationsToDetach, $userRelation) {
                $model = $model instanceof Model
                    ? $model
                    : $this->findOrFail($model);

                if (! in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                    throw new RuntimeException('El modelo no soporta SoftDeletes');
                }

                if ($model->trashed()) {
                    return;
                }

                if ($status !== null) {
                    if (Schema::hasColumn($model->getTable(), 'status')) {
                        $model->update(['status' => $status]);
                    } elseif (Schema::hasColumn($model->getTable(), 'status_id')) {
                        $model->update(['status_id' => $status]);
                    }
                }

                foreach ($relationsToDetach as $relation) {
                    if (method_exists($model, $relation)) {
                        $model->$relation()->detach();
                    }
                }

                // Bug corregido: antes solo llamaba a softDeleteRelatedUser()
                // cuando $userRelation NO se pasaba (usando su default 'user'
                // interno), y sin propagar el parámetro. Pasar userRelation
                // explícitamente (ej. AdminTreasurerCrudService::delete(),
                // AdminSubsidiaryCrudService::delete()) desactivaba por
                // completo el soft-delete del User asociado, contradiciendo
                // el docblock del método — el dominio quedaba DELETED pero su
                // User seguía ACTIVE.
                if ($userRelation) {
                    $this->softDeleteRelatedUser($model, $status, $userRelation);
                }

                $model->delete();
            });

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function softDeleteRelatedUser(Model $model, ?string $status, ?string $userRelation = 'user') : void
    {
        // Si no se definió relación o no existe el método, no hacemos nada.
        if (! $userRelation || ! method_exists($model, $userRelation)) {
            return;
        }

        // Cargar relación solo si no está cargada (optimización).
        $model->loadMissing($userRelation);

        $user = $model->$userRelation;

        // Si la relación no devolvió un Model válido, no hacemos nada.
        if (! $user instanceof Model) {
            return;
        }

        // Validar que el usuario soporte SoftDeletes.
        if (! in_array(SoftDeletes::class, class_uses_recursive($user), true)) {
            throw new RuntimeException('El usuario no soporta SoftDeletes');
        }

        // Si ya está eliminado, no hacemos nada.
        if ($user->trashed()) {
            return;
        }

        // Si se indicó status, actualizarlo antes del delete.
        if ($status !== null) {
            if (Schema::hasColumn($user->getTable(), 'status')) {
                $user->update(['status' => $status]);
            } elseif (Schema::hasColumn($user->getTable(), 'status_id')) {
                $user->update(['status_id' => $status]);
            }
        }

        // Finalmente eliminamos el usuario (SoftDelete).
        $user->delete();
    }

    public function toggleStatus(Model $model) : bool
    {
        $newStatus = $this->statusChanger->toggle((string) $model->status);

        return $this->changeStatus($model, $newStatus);
    }

    /**
     * {@inheritDoc}
     */
    public function update(string|int $id, array $data) : Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function where(array $conditions, array $columns = ['*']) : Collection
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->get($columns);
    }
}

<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

/**
 * Interface BaseRepositoryInterface
 *
 * Contrato base para todos los repositorios del sistema.
 *
 * Define operaciones CRUD comunes y métodos de consulta reutilizables.
 * Este contrato representa la capa de acceso a datos y NO debe contener
 * lógica de negocio.
 *
 * ⚠️ Nota:
 * - Algunos métodos existen por compatibilidad legacy.
 * - Los repositorios concretos pueden extender este contrato con métodos específicos.
 */
interface BaseRepositoryInterface
{
    /**
     * Obtener todos los registros.
     *
     * @param array<int,string> $columns Columnas a seleccionar
     *
     * @return Collection<int, Model>
     */
    public function all(array $columns = ['*']) : Collection;

    /**
     * Cambiar estado del modelo (y usuario relacionado opcional).
     *
     * @param string|null $newStatus    Nuevo estado (opcional)
     * @param string|null $userRelation Relación belongsTo hacia User
     */
    public function changeStatus(
        Model $model,
        ?string $newStatus = null,
        ?string $userRelation = null,
    ) : bool;

    /**
     * Contar registros con condiciones opcionales.
     *
     * @param array<string,mixed> $conditions Condiciones de conteo
     */
    public function count(array $conditions = []) : int;

    /**
     * Crear un nuevo registro.
     *
     * ⚠️ El array representa datos ya validados.
     * ⚠️ La lógica de negocio debe ocurrir antes de este punto.
     *
     * @param array<string,mixed> $data Datos para crear el registro
     */
    public function create(array $data) : Model;

    /**
     * Elimina un registro por su ID y actualiza su estado a "DELETED".
     *
     * Primero actualiza el campo `status` a "DELETED" y luego realiza la eliminación física.
     *
     * @param string|int $id ID del registro a eliminar
     *
     * @return bool indica si la eliminación fue exitosa
     */
    public function delete(string|int $id) : bool;

    /**
     * Verificar si existe un registro por ID.
     *
     * @param string|int $id ID del registro
     */
    public function exists(string|int $id) : bool;

    /**
     * Verificar si existe un registro por un campo dinámico.
     *
     * @param string $field Nombre del campo
     * @param mixed  $value Valor a comparar
     */
    public function existsByField(string $field, mixed $value) : bool;

    /**
     * Buscar un registro por ID.
     *
     * @param string|int        $id      ID del registro
     * @param array<int,string> $columns Columnas a seleccionar
     */
    public function find(string|int $id, array $columns = ['*']) : ?Model;

    /**
     * Buscar un registro por ID o lanzar excepción.
     *
     * @param string|int        $id      ID del registro
     * @param array<int,string> $columns Columnas a seleccionar
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string|int $id, array $columns = ['*']) : Model;

    /**
     * Busca un registro por ID cargando relaciones específicas.
     *
     * @param array<int,string> $relations Relaciones Eloquent a cargar
     * @param string|int        $id        ID del registro a buscar
     * @param array<int,string> $fields    Campos a seleccionar
     *
     * @return Model|null Retorna el modelo con las relaciones cargadas o null si no se encuentra
     */
    public function findWithRelations(array $relations, string|int $id, array $fields = ['*']) : ?Model;

    /**
     * Obtener el primer registro que coincida con las condiciones.
     *
     * @param array<string,mixed> $conditions Condiciones de búsqueda
     * @param array<int,string>   $columns    Columnas a seleccionar
     */
    public function first(array $conditions, array $columns = ['*']) : ?Model;

    /**
     * Obtener todos los registros con relaciones cargadas.
     *
     * @param array<int,string> $relations Relaciones Eloquent
     * @param array<int,string> $fields    Campos de la tabla principal
     *
     * @return Collection<int, Model>
     */
    public function getAllWithRelations(
        array $relations = [],
        array $fields = ['*'],
    ) : Collection;

    /**
     * Obtener registros por un conjunto de IDs.
     *
     * ⚠️ Método legacy: preferir consultas más expresivas cuando sea posible.
     *
     * @param array<int,string|int> $ids    IDs a buscar
     * @param array<int,string>     $fields Campos a seleccionar
     *
     * @return Collection<int, Model>
     */
    public function getByIds(array $ids, array $fields = ['*']) : Collection;

    /**
     * Obtener registros por IDs con campos seleccionados y relaciones cargadas.
     *
     * @param array<int,string> $relations Relaciones Eloquent a cargar
     * @param array<int|string> $ids       IDs a buscar
     * @param array<int,string> $fields    Campos a seleccionar
     *
     * @return Collection<int, Model>
     */
    public function getByIdsWithRelations(array $relations, array $ids, array $fields = ['*']) : Collection;

    /**
     * Obtener registros filtrados por estado.
     *
     * @param string            $status Estado del registro
     * @param array<int,string> $fields Campos a seleccionar
     *
     * @return Collection<int, Model>
     */
    public function getByStatus(string $status, array $fields = ['*']) : Collection;

    /**
     * Obtiene la instancia actual del modelo Eloquent asociado al repositorio.
     *
     * @return Model instancia del modelo Eloquent usada por el repositorio
     */
    public function getModel() : Model;

    /**
     * Obtener el valor de un campo específico por ID.
     *
     * Útil para consultas simples sin hidratar el modelo completo.
     *
     * @param string|int $id    ID del registro
     * @param string     $field Campo a obtener
     *
     * @return mixed|null
     */
    public function getValueById(string|int $id, string $field) : mixed;

    /**
     * Obtener el valor de un campo específico por user_id (ID del usuario).
     *
     * Útil para consultas simples sin hidratar el modelo completo.
     *
     * @param string|int $userId ID del usuario del registro
     * @param string     $field  Campo a obtener
     *
     * @return mixed|null
     */
    public function getValueByUserId(string|int $userId, string $field) : mixed;

    /**
     * Paginar resultados.
     *
     * @param int               $perPage Registros por página
     * @param array<int,string> $columns Columnas a seleccionar
     * @param int               $page    Página actual
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        int $page = 1,
    ) : LengthAwarePaginator;

    /**
     * Paginar resultados con relaciones y filtros opcionales.
     *
     * @param int                 $perPage    Registros por página
     * @param int                 $page       Página actual
     * @param array<int,string>   $relations  Relaciones Eloquent a cargar
     * @param array<string,mixed> $conditions Condiciones de filtrado (campo => valor)
     * @param array<int,string>   $fields     Columnas a seleccionar
     */
    public function paginateWithRelations(
        int $perPage = 15,
        int $page = 1,
        array $relations = [],
        array $conditions = [],
        array $fields = ['*'],
    ) : LengthAwarePaginator;

    /**
     * Retorna el query builder del modelo asociado.
     */
    public function query() : Builder;

    /**
     * Restablece la instancia del modelo Eloquent asociada al repositorio.
     *
     * Este método reinicializa la propiedad interna del modelo para limpiar
     * cualquier estado previo y preparar una nueva instancia limpia.
     */
    public function resetModel() : void;

    /**
     * Soft delete genérico con soporte de relaciones.
     *
     * @param string|null        $status            Estado a aplicar (ej: DELETED)
     * @param array<int, string> $relationsToDetach Relaciones many-to-many
     * @param string|null        $userRelation      Relación belongsTo hacia User
     */
    public function softDelete(
        int|string|Model $model,
        ?string $status = 'DELETED',
        array $relationsToDetach = [],
        ?string $userRelation = null,
    ) : bool;

    /**
     * Realiza un soft delete del usuario relacionado a un modelo (si aplica).
     *
     * - Verifica que la relación exista en el modelo.
     * - Carga la relación si no está cargada.
     * - Valida que el usuario sea un Model.
     * - Valida que el usuario soporte SoftDeletes.
     * - Si no está eliminado, actualiza su status (si corresponde) y luego lo elimina.
     *
     * @param Model       $model        modelo principal desde donde se obtiene la relación
     * @param string|null $status       estado a aplicar antes del delete (ej: DELETED)
     * @param string|null $userRelation nombre del método relación (ej: "user")
     *
     * @throws RuntimeException si el usuario relacionado no soporta SoftDeletes
     */
    public function softDeleteRelatedUser(Model $model, ?string $status, ?string $userRelation = 'user') : void;

    /**
     * Alterna el estado del modelo entre ACTIVE e INACTIVE.
     *
     * El cálculo del nuevo estado se delega al StatusChanger
     * para respetar reglas de dominio.
     *
     *
     * @throws InvalidArgumentException Si el estado no permite transición
     */
    public function toggleStatus(Model $model) : bool;

    /**
     * Actualizar un registro existente.
     *
     * @param string|int          $id   ID del registro
     * @param array<string,mixed> $data Datos a actualizar
     */
    public function update(string|int $id, array $data) : Model;

    /**
     * Buscar registros por condiciones simples.
     *
     * @param array<string,mixed> $conditions Condiciones de búsqueda
     * @param array<int,string>   $columns    Columnas a seleccionar
     *
     * @return Collection<int, Model>
     */
    public function where(array $conditions, array $columns = ['*']) : Collection;
}

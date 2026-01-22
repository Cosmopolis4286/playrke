<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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
     * @return Collection<int, Model>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Buscar un registro por ID.
     *
     * @param string|int $id ID del registro
     * @param array<int,string> $columns Columnas a seleccionar
     * @return Model|null
     */
    public function find(string|int $id, array $columns = ['*']): ?Model;

    /**
     * Buscar un registro por ID o lanzar excepción.
     *
     * @param string|int $id ID del registro
     * @param array<int,string> $columns Columnas a seleccionar
     *
     * @return Model
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string|int $id, array $columns = ['*']): Model;

    /**
     * Obtener el valor de un campo específico por ID.
     *
     * Útil para consultas simples sin hidratar el modelo completo.
     *
     * @param string|int $id ID del registro
     * @param string $field Campo a obtener
     *
     * @return mixed|null
     */
    public function getValueById(string|int $id, string $field): mixed;

    /**
     * Obtener el valor de un campo específico por user_id (ID del usuario).
     *
     * Útil para consultas simples sin hidratar el modelo completo.
     *
     * @param string|int $userId ID del usuario del registro
     * @param string $field Campo a obtener
     *
     * @return mixed|null
     */
    public function getValueByUserId(string|int $userId, string $field): mixed;

    /**
     * Obtener registros por un conjunto de IDs.
     *
     * ⚠️ Método legacy: preferir consultas más expresivas cuando sea posible.
     *
     * @param array<int,string|int> $Ids IDs a buscar
     * @param array<int,string> $fields Campos a seleccionar
     *
     * @return Collection<int, Model>
     */
    public function getByIds(array $Ids, array $fields = ['*']): Collection;

    /**
     * Verificar si existe un registro por un campo dinámico.
     *
     * @param string $field Nombre del campo
     * @param mixed $value Valor a comparar
     *
     * @return bool
     */
    public function existsByField(string $field, mixed $value): bool;

    /**
     * Crear un nuevo registro.
     *
     * ⚠️ El array representa datos ya validados.
     * ⚠️ La lógica de negocio debe ocurrir antes de este punto.
     *
     * @param array<string,mixed> $data Datos para crear el registro
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Actualizar un registro existente.
     *
     * @param string|int $id ID del registro
     * @param array<string,mixed> $data Datos a actualizar
     *
     * @return Model
     */
    public function update(string|int $id, array $data): Model;

    /**
     * Eliminar un registro.
     *
     * La implementación decide si es soft delete o hard delete.
     *
     * @param string|int $id ID del registro
     * @return bool
     */
    public function delete(string|int $id): bool;

    /**
     * Buscar registros por condiciones simples.
     *
     * @param array<string,mixed> $conditions Condiciones de búsqueda
     * @param array<int,string> $columns Columnas a seleccionar
     *
     * @return Collection<int, Model>
     */
    public function where(array $conditions, array $columns = ['*']): Collection;

    /**
     * Paginar resultados.
     *
     * @param int $perPage Registros por página
     * @param array<int,string> $columns Columnas a seleccionar
     * @param int $page Página actual
     *
     * @return LengthAwarePaginator
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        int $page = 1
    ): LengthAwarePaginator;

    /**
     * Verificar si existe un registro por ID.
     *
     * @param string|int $id ID del registro
     * @return bool
     */
    public function exists(string|int $id): bool;

    /**
     * Contar registros con condiciones opcionales.
     *
     * @param array<string,mixed> $conditions Condiciones de conteo
     * @return int
     */
    public function count(array $conditions = []): int;

    /**
     * Obtener el primer registro que coincida con las condiciones.
     *
     * @param array<string,mixed> $conditions Condiciones de búsqueda
     * @param array<int,string> $columns Columnas a seleccionar
     *
     * @return Model|null
     */
    public function first(array $conditions, array $columns = ['*']): ?Model;

    /**
     * Obtener todos los registros con relaciones cargadas.
     *
     * @param array<int,string> $relations Relaciones Eloquent
     * @param array<int,string> $fields Campos de la tabla principal
     *
     * @return Collection<int, Model>
     */
    public function getAllWithRelations(
        array $relations = [],
        array $fields = ['*']
    ): Collection;

    /**
     * Obtener registros por IDs con campos seleccionados y relaciones cargadas.
     *
     * @param array<int,string> $relations Relaciones Eloquent a cargar
     * @param array<int|string> $Ids IDs a buscar
     * @param array<int,string> $fields Campos a seleccionar
     *
     * @return Collection<int, Model>
     */
    public function getByIdsWithRelations(array $relations = [], array $Ids, array $fields = ['*']): Collection;

    /**
     * Paginar resultados con relaciones y filtros opcionales.
     *
     * @param int $perPage Registros por página
     * @param int $page Página actual
     * @param array<int,string> $relations Relaciones Eloquent a cargar
     * @param array<string,mixed> $conditions Condiciones de filtrado (campo => valor)
     * @param array<int,string> $fields Columnas a seleccionar
     *
     * @return LengthAwarePaginator
     */
    public function paginateWithRelations(
        int $perPage = 15,
        int $page = 1,
        array $relations = [],
        array $conditions = [],
        array $fields = ['*']
    ): LengthAwarePaginator;

    /**
     * Obtener registros filtrados por estado.
     *
     * @param string $status Estado del registro
     * @param array<int,string> $fields Campos a seleccionar
     *
     * @return Collection<int, Model>
     */
    public function getByStatus(string $status, array $fields = ['*']): Collection;
}

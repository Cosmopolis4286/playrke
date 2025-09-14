<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Contrato base para todos los repositorios
 * Define métodos comunes que todos los repositorios deben implementar
 */
interface BaseRepositoryContract
{
    /**
     * Obtener todos los registros
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Buscar un registro por ID
     *
     * @param string|int $id ID del registro
     * @param array $columns Columnas a seleccionar
     * @return Model|null
     */
    public function find(string|int $id, array $columns = ['*']): ?Model;

    /**
     * Buscar un registro por ID o lanzar excepción
     *
     * @param string|int $id ID del registro
     * @param array $columns Columnas a seleccionar
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string|int $id, array $columns = ['*']): Model;

    /**
     * Crear un nuevo registro
     *
     * @param array $data Datos para crear el registro
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Actualizar un registro existente
     *
     * @param string|int $id ID del registro a actualizar
     * @param array $data Datos para actualizar
     * @return Model
     */
    public function update(string|int $id, array $data): Model;

    /**
     * Eliminar un registro
     *
     * @param string|int $id ID del registro a eliminar
     * @return bool
     */
    public function delete(string|int $id): bool;

    /**
     * Buscar registros con condiciones
     *
     * @param array $conditions Condiciones de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Collection
     */
    public function where(array $conditions, array $columns = ['*']): Collection;

    /**
     * Paginar resultados
     *
     * @param int $perPage Registros por página
     * @param array $columns Columnas a seleccionar
     * @param int $page Página actual
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], int $page = 1): LengthAwarePaginator;

    /**
     * Verificar si existe un registro
     *
     * @param string|int $id ID del registro
     * @return bool
     */
    public function exists(string|int $id): bool;

    /**
     * Contar registros
     *
     * @param array $conditions Condiciones de conteo
     * @return int
     */
    public function count(array $conditions = []): int;

    /**
     * Buscar primer registro que coincida
     *
     * @param array $conditions Condiciones de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Model|null
     */
    public function first(array $conditions, array $columns = ['*']): ?Model;
}
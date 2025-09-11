<?php

namespace App\Repositories\User\Roles;

use App\Models\Users\Roles\Role;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para consultas relacionadas con roles.
 *
 * Solo maneja queries y retorno de datos; no contiene lógica de negocio.
 */
class RoleRepository
{
    /**
     * Obtener un valor específico de un rol por ID.
     */
    public static function getValueById(int $id, string $column): mixed
    {
        return Role::where('id', $id)->value($column);
    }

    /**
     * Obtener un valor específico de un rol por prefijo.
     */
    public static function getColumnByPrefix(string $prefix, string $column = 'id'): mixed
    {
        return Role::where('prefix', $prefix)->value($column);
    }

    /**
     * Verificar si un rol existe y está activo.
     */
    public static function existsActive(int $id): bool
    {
        return Role::where('id', $id)->where('status', 1)->exists();
    }

    /**
     * Listar todos los roles activos.
     *
     * @return Collection<int, Role>
     */
    public static function getAllActive(): Collection
    {
        return Role::where('status', 1)->get();
    }

    /**
     * Listar todos los roles (sin filtrar por status).
     *
     * @return Collection<int, Role>
     */
    public static function getAll(): Collection
    {
        return Role::all();
    }

    /**
     * Obtener todos los usuarios asociados a un rol por prefijo.
     *
     * @param string $prefix
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUsersByPrefix(string $prefix): Collection
    {
        $role = Role::where('prefix', $prefix)->first();
        return $role ? $role->users()->get() : collect();
    }
}

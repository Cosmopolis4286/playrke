<?php

namespace App\Repositories\User;

use App\Models\UserType;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para consultas relacionadas con los tipos de usuario (UserType).
 *
 * Solo maneja queries y retorno de datos; no contiene lógica de negocio.
 */
class UserTypeRepository
{
    /**
     * Obtener un valor específico de un tipo de usuario por ID.
     *
     * @param int $id
     * @param string $column Nombre de la columna a retornar
     * @return mixed
     */
    public static function getColumnById(int $id, string $column): mixed
    {
        return UserType::where('id', $id)->value($column);
    }

    /**
     * Obtener un valor específico de un tipo de usuario por prefijo.
     *
     * @param string $prefix
     * @param string $column Nombre de la columna a retornar (default: 'id')
     * @return mixed
     */
    public static function getColumnByPrefix(string $prefix, string $column = 'id'): mixed
    {
        return UserType::where('prefix', $prefix)->value($column);
    }

    /**
     * Verificar si un tipo de usuario existe y está activo.
     *
     * @param int $id
     * @return bool
     */
    public static function existsActive(int $id): bool
    {
        return UserType::where('id', $id)->where('status', 1)->exists();
    }

    /**
     * Listar todos los tipos de usuario activos.
     *
     * @return Collection<int, UserType>
     */
    public static function getAllActive(): Collection
    {
        return UserType::where('status', 1)->get();
    }

    /**
     * Listar todos los tipos de usuario (sin filtrar por status).
     *
     * @return Collection<int, UserType>
     */
    public static function getAll(): Collection
    {
        return UserType::all();
    }
}

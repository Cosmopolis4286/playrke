<?php

namespace App\Contracts\Roles\Admin;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Roles\Admin;

/**
 * Contrato para el repository de administradores
 */
interface AdminRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtiene administrador por ID de usuario
     *
     * @param mixed $userId ID del usuario
     * @param array $fields Campos a seleccionar
     * @return Admin|null
     */
    public function getByUserId($userId, array $fields = ['*']): ?Admin;

    /**
     * Obtiene administrador por código UUID
     *
     * @param string $code Código UUID
     * @param array $fields Campos a seleccionar
     * @return Admin|null
     */
    public function getByCodeUUID(string $code, array $fields = ['*']): ?Admin;

    /**
     * Obtiene valor por código UUID
     *
     * @param string $code Código UUID
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueByCodeUUID(string $code, string $field);

    /**
     * Verifica si existe un valor en una columna
     *
     * @param string $column Columna a verificar
     * @param mixed $value Valor a buscar
     * @return bool
     */
    public function existsValue(string $column, $value): bool;

    /**
     * Obtiene administradores por país
     *
     * @param mixed $countryId ID del país
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCountry($countryId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Obtiene administradores inactivos
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getInactiveAdmins(): \Illuminate\Database\Eloquent\Collection;

    /**
     * Obtiene administradores bloqueados
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBlockedAdmins(): \Illuminate\Database\Eloquent\Collection;

    /**
     * Actualiza estado de un administrador
     *
     * @param mixed $id ID del administrador
     * @param string $status Nuevo estado
     * @return bool
     */
    public function updateStatus($id, string $status): bool;

    /**
     * Activa un administrador
     *
     * @param mixed $id ID del administrador
     * @return bool
     */
    public function activate($id): bool;

    /**
     * Desactiva un administrador
     *
     * @param mixed $id ID del administrador
     * @return bool
     */
    public function deactivate($id): bool;

    /**
     * Bloquea un administrador
     *
     * @param mixed $id ID del administrador
     * @return bool
     */
    public function block($id): bool;
}

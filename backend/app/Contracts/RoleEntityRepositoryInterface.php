<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Contrato base compartido para repositorios de entidades de rol
 * (Admin, Commerce, etc.) que exponen operaciones comunes de
 * búsqueda por usuario/código, país, estado (activar/desactivar/bloquear).
 *
 * Las interfaces específicas de cada rol extienden este contrato y
 * añaden únicamente sus métodos propios. Los tipos de retorno aquí
 * son deliberadamente relajados (Model/Collection) porque PHP no
 * soporta generics; las implementaciones concretas devuelven su tipo
 * específico (ej. Admin, Commerce) mediante covarianza de retorno,
 * lo cual es válido en PHP 8+.
 */
interface RoleEntityRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Activa una entidad de rol
     *
     * @param mixed $id ID de la entidad
     */
    public function activate($id) : bool;

    /**
     * Bloquea una entidad de rol
     *
     * @param mixed $id ID de la entidad
     */
    public function block($id) : bool;

    /**
     * Desactiva una entidad de rol
     *
     * @param mixed $id ID de la entidad
     */
    public function deactivate($id) : bool;

    /**
     * Verifica si existe un valor en una columna
     *
     * @param string $column Columna a verificar
     * @param mixed  $value  Valor a buscar
     */
    public function existsValue(string $column, $value) : bool;

    /**
     * Obtiene entidades de rol bloqueadas
     */
    public function getBlockedRecords() : Collection;

    /**
     * Obtiene la entidad de rol por código UUID
     *
     * @param string $code   Código UUID
     * @param array  $fields Campos a seleccionar
     */
    public function getByCodeUUID(string $code, array $fields = ['*']) : ?Model;

    /**
     * Obtiene entidades de rol por país
     *
     * @param mixed $countryId ID del país
     */
    public function getByCountry($countryId) : Collection;

    /**
     * Obtiene la entidad de rol por ID de usuario
     *
     * @param mixed $userId ID del usuario
     * @param array $fields Campos a seleccionar
     */
    public function getByUserId($userId, array $fields = ['*']) : ?Model;

    /**
     * Obtiene entidades de rol inactivas
     */
    public function getInactiveRecords() : Collection;

    /**
     * Obtiene valor por código UUID
     *
     * @param string $code  Código UUID
     * @param string $field Campo a obtener
     *
     * @return mixed
     */
    public function getValueByCodeUUID(string $code, string $field);

    /**
     * Actualiza estado de una entidad de rol
     *
     * @param mixed  $id     ID de la entidad
     * @param string $status Nuevo estado
     */
    public function updateStatus($id, string $status) : bool;
}

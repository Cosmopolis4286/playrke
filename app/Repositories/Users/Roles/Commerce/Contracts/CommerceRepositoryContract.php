<?php

namespace App\Repositories\Users\Roles\Commerce;

use App\Repositories\Base\Contracts\BaseRepositoryContract;
use App\Models\Users\Roles\Commerce\Commerce;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de comercios
 */
interface CommerceRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtiene comercio por ID de usuario
     *
     * @param mixed $userId ID del usuario
     * @param array $fields Campos a seleccionar
     * @return Commerce|null
     */
    public function getByUserId($userId, array $fields = ['*']): ?Commerce;

    /**
     * Obtiene valor por ID de usuario
     *
     * @param mixed $userId ID del usuario
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueByUserId($userId, string $field);

    /**
     * Obtiene comercio por código RKE
     *
     * @param string $code Código RKE
     * @param array $fields Campos a seleccionar
     * @return Commerce|null
     */
    public function getByCodeRKE(string $code, array $fields = ['*']): ?Commerce;

    /**
     * Obtiene valor por código RKE
     *
     * @param string $code Código RKE
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueByCodeRKE(string $code, string $field);

    /**
     * Obtiene comercio por código UUID
     *
     * @param string $code Código UUID
     * @param array $fields Campos a seleccionar
     * @return Commerce|null
     */
    public function getByCodeUUID(string $code, array $fields = ['*']): ?Commerce;

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
     * Obtiene todos los comercios activos
     *
     * @param array $fields Campos a seleccionar
     * @return Collection
     */
    public function getActiveCommerces(array $fields = ['*']): Collection;

    /**
     * Obtiene comercios por país
     *
     * @param mixed $countryId ID del país
     * @return Collection
     */
    public function getByCountry($countryId): Collection;

    /**
     * Obtiene comercios por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getByCurrency($currencyId): Collection;

    /**
     * Obtiene comercios por estado
     *
     * @param string $status Estado del comercio
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * Obtiene comercios inactivos
     *
     * @return Collection
     */
    public function getInactiveCommerces(): Collection;

    /**
     * Obtiene comercios bloqueados
     *
     * @return Collection
     */
    public function getBlockedCommerces(): Collection;

    /**
     * Actualiza estado de un comercio
     *
     * @param mixed $id ID del comercio
     * @param string $status Nuevo estado
     * @return bool
     */
    public function updateStatus($id, string $status): bool;

    /**
     * Activa un comercio
     *
     * @param mixed $id ID del comercio
     * @return bool
     */
    public function activate($id): bool;

    /**
     * Desactiva un comercio
     *
     * @param mixed $id ID del comercio
     * @return bool
     */
    public function deactivate($id): bool;

    /**
     * Bloquea un comercio
     *
     * @param mixed $id ID del comercio
     * @return bool
     */
    public function block($id): bool;

    /**
     * Actualiza comisión de un comercio
     *
     * @param mixed $id ID del comercio
     * @param float $commission Nueva comisión
     * @return bool
     */
    public function updateCommission($id, float $commission): bool;
}
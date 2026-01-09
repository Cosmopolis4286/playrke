<?php

namespace App\Repositories\Users\Roles\Commerce\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de países y monedas de comercios
 */
interface CommerceCurrencyRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtiene monedas disponibles para un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed|null $exceptCurrencyId ID de moneda a excluir
     * @return array
     */
    public function getAvailableCurrencies($commerceId, $exceptCurrencyId = null): array;

    /**
     * Obtiene todos los países y monedas disponibles para un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @param array $fields Campos a seleccionar
     * @return Collection
     */
    public function getAllAvailableCountriesCurrencies($commerceId, array $fields = ['*']): Collection;

    /**
     * Obtiene países disponibles para un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return array
     */
    public function getAvailableCountries($commerceId): array;

    /**
     * Obtiene registros por comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return Collection
     */
    public function getByCommerce($commerceId): Collection;

    /**
     * Obtiene registros por país
     *
     * @param mixed $countryId ID del país
     * @return Collection
     */
    public function getByCountry($countryId): Collection;

    /**
     * Obtiene registros por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getByCurrency($currencyId): Collection;

    /**
     * Obtiene registros por usuario creador
     *
     * @param mixed $creatorUserId ID del usuario creador
     * @return Collection
     */
    public function getByCreatorUser($creatorUserId): Collection;

    /**
     * Obtiene registros por nivel
     *
     * @param int $level Nivel de asignación
     * @return Collection
     */
    public function getByLevel(int $level): Collection;

    /**
     * Obtiene registros por estado
     *
     * @param string $status Estado del registro
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * Obtiene registros activos
     *
     * @return Collection
     */
    public function getActiveRecords(): Collection;

    /**
     * Obtiene registros inactivos
     *
     * @return Collection
     */
    public function getInactiveRecords(): Collection;

    /**
     * Verifica si existe combinación comercio-país-moneda
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $countryId ID del país
     * @param mixed $currencyId ID de la moneda
     * @return bool
     */
    public function existsCombination($commerceId, $countryId, $currencyId): bool;

    /**
     * Actualiza estado de un registro
     *
     * @param mixed $id ID del registro
     * @param string $status Nuevo estado
     * @return bool
     */
    public function updateStatus($id, string $status): bool;

    /**
     * Actualiza nivel de un registro
     *
     * @param mixed $id ID del registro
     * @param int $level Nuevo nivel
     * @return bool
     */
    public function updateLevel($id, int $level): bool;

    /**
     * Activa registros de un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return int Número de registros actualizados
     */
    public function activateByCommerce($commerceId): int;

    /**
     * Desactiva registros de un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return int Número de registros actualizados
     */
    public function deactivateByCommerce($commerceId): int;
}

<?php

namespace App\Contracts\Roles\Commerce;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de países y monedas de comercios
 */
interface CommerceCurrencyRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtiene monedas disponibles para un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return array
     */
    public function getAvailableCurrenciesIds($commerceId): array;

    /**
     * Obtiene registros por comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return Collection
     */
    public function getByCommerce($commerceId): Collection;

    /**
     * Obtiene registros por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getByCurrency($currencyId): Collection;

    /**
     * Obtiene registros por nivel
     *
     * @param int $level Nivel de asignación
     * @return Collection
     */
    public function getByLevel(int $level): Collection;

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

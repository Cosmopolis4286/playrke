<?php

namespace App\Contracts\Roles\Treasurer;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de países y monedas de tesoreros
 */
interface TreasurerCurrencyRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtiene monedas disponibles para un tesorero
     *
     * @param mixed $treasurerId ID del tesorero
     * @return array
     */
    public function getAvailableCurrenciesIds($treasurerId): array;

    /**
     * Obtiene currency_id con su level mínimo para un tesorero (solo ACTIVE).
     * Retorna array indexado: currency_id => level.
     *
     * @param mixed $treasurerId ID del tesorero
     * @return array<string, int>
     */
    public function getAvailableCurrenciesWithLevel($treasurerId): array;

    /**
     * Obtiene los códigos ISO de las monedas disponibles para un tesorero
     *
     * @param mixed $treasurerId ID del tesorero
     * @return array Lista de códigos ISO
     */
    public function getAvailableCurrenciesIsoCodes($treasurerId): array;

    /**
     * Obtiene registros por tesorero
     *
     * @param mixed $treasurerId ID del tesorero
     * @return Collection
     */
    public function getByTreasurer($treasurerId): Collection;

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
     * Activa registros de un tesorero
     *
     * @param mixed $treasurerId ID del tesorero
     * @return int Número de registros actualizados
     */
    public function activateByTreasurer($treasurerId): int;

    /**
     * Desactiva registros de un tesorero
     *
     * @param mixed $treasurerId ID del tesorero
     * @return int Número de registros actualizados
     */
    public function deactivateByTreasurer($treasurerId): int;
}

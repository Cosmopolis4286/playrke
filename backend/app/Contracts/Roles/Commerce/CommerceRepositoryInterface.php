<?php

namespace App\Contracts\Roles\Commerce;

use App\Contracts\RoleEntityRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de comercios
 *
 * Los métodos comunes (getByUserId, getByCodeUUID, getValueByCodeUUID,
 * existsValue, getByCountry, getInactiveRecords, getBlockedRecords,
 * updateStatus, activate, deactivate, block) están definidos en
 * RoleEntityRepositoryInterface. Aquí solo quedan los métodos propios
 * de comercios.
 */
interface CommerceRepositoryInterface extends RoleEntityRepositoryInterface
{
    /**
     * Obtiene comercios por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getByCurrency($currencyId): Collection;

    /**
     * Actualiza comisión de un comercio
     *
     * @param mixed $id ID del comercio
     * @param float $commission Nueva comisión
     * @return bool
     */
    public function updateCommission($id, float $commission): bool;
}

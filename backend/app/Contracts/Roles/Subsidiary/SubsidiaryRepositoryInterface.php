<?php

namespace App\Contracts\Roles\Subsidiary;

use App\Contracts\RoleEntityRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de agencias/sucursales (Subsidiary).
 * Los métodos comunes (getByUserId, getByCodeUUID, existsValue, getByCountry,
 * getInactiveRecords, getBlockedRecords, updateStatus, activate, deactivate,
 * block) están definidos en RoleEntityRepositoryInterface.
 */
interface SubsidiaryRepositoryInterface extends RoleEntityRepositoryInterface
{
    /**
     * Obtiene agencias por comercio (comercializadora) padre.
     */
    public function getByCommerce($commerceId): Collection;
}

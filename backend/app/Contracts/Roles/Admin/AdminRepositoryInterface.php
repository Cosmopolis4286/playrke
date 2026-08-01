<?php

namespace App\Contracts\Roles\Admin;

use App\Contracts\RoleEntityRepositoryInterface;

/**
 * Contrato para el repository de administradores
 *
 * Los métodos comunes (getByUserId, getByCodeUUID, getValueByCodeUUID,
 * existsValue, getByCountry, getInactiveRecords, getBlockedRecords,
 * updateStatus, activate, deactivate, block) están definidos en
 * RoleEntityRepositoryInterface.
 */
interface AdminRepositoryInterface extends RoleEntityRepositoryInterface
{
}

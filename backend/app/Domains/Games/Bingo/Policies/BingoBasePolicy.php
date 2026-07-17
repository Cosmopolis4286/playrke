<?php

namespace App\Domains\Games\Bingo\Policies;

use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy base común a las salas de Bingo (manuales y automáticas).
 *
 * Centraliza el rol y los estados requeridos, compartidos por
 * BingoRoomPolicy y BingoRoomAutomaticPolicy.
 */
abstract class BingoBasePolicy extends BaseRolePolicy
{
    protected function requiredRole(): string|array
    {
        return ['superadmin', 'admin', 'commerce', 'customer'];
    }

    protected function forbiddenStatuses(): array
    {
        return ['DELETED', 'BLOCKED'];
    }
}

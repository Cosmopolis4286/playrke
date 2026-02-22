<?php

namespace App\Policies\Games\Bingo\Room;

use App\Models\Games\Bingo\Room\BingoRoomAutomatic;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para el dominio BingoRoomAutomatic.
 *
 * Reglas:
 * - Solo usuarios con roles permitidos pueden operar
 * - Vendedores en estados DELETED o BLOCKED no pueden mutar
 * - Compatible con authorizeResource()
 */
class BingoRoomAutomaticPolicy extends BaseRolePolicy
{
    /**
     * Roles requeridos para operar sobre vendedores.
     *
     * @return string|string[]
     */
    protected function requiredRole(): string|array
    {
        return ['admin', 'commerce', 'customer'];
    }

    /**
     * Estados que bloquean operaciones mutables.
     *
     * @return string[]
     */
    protected function forbiddenStatuses(): array
    {
        return ['DELETED', 'BLOCKED'];
    }

    /**
     * Alternar estado de una sala de bingo automatica (autogenerable).
     */
    public function toggleStatus(User $user, BingoRoomAutomatic $bingoRoom): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($bingoRoom);
    }
}

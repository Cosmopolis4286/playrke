<?php

namespace App\Domains\Games\Bingo\Policies;

use App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic;
use App\Models\User;

/**
 * Policy de autorización para el dominio BingoRoomAutomatic.
 *
 * Reglas:
 * - Solo usuarios con roles permitidos pueden operar
 * - Salas en estados DELETED o BLOCKED no pueden mutar
 * - Compatible con authorizeResource()
 *
 * A diferencia de BingoRoomPolicy, aquí no se exige ser el creador de la
 * sala: las salas automáticas son un recurso de plataforma, no de un
 * usuario individual.
 */
class BingoRoomAutomaticPolicy extends BingoBasePolicy
{
    /**
     * Alternar estado de una sala de bingo automática (autogenerable).
     */
    public function toggleStatus(User $user, BingoRoomAutomatic $bingoRoom): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($bingoRoom);
    }
}

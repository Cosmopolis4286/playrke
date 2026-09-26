<?php

namespace App\Domains\Games\Bingo\Policies;

use App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic;
use App\Models\User;

/**
 * Policy de autorización para el dominio BingoRoomAutomatic.
 *
 * Reglas:
 * - Solo admin/superadmin pueden gestionar (crear/editar/eliminar/alternar
 *   estado) — a diferencia de BingoRoomPolicy (salas manuales, abiertas a
 *   commerce/subsidiary/customer con permiso games.bingo.manage), las salas
 *   automáticas son un recurso de PLATAFORMA, no de un negocio individual:
 *   no tienen "creador" dueño ni tiene sentido que un comercio las administre.
 * - Salas en estados DELETED o BLOCKED no pueden mutar
 * - Compatible con authorizeResource()
 *
 * Antes heredaba create()/update()/delete() de BaseRolePolicy sin
 * sobreescribirlos, así que solo exigían requiredRole() de BingoBasePolicy
 * (incluye commerce/subsidiary/customer) — cualquiera de esos roles podía
 * mutar un recurso de plataforma pensado exclusivamente para admin/superadmin.
 */
class BingoRoomAutomaticPolicy extends BingoBasePolicy
{
    protected function isElevated(User $user): bool
    {
        return $user->hasRole(['superadmin', 'admin']);
    }

    public function create(User $user): bool
    {
        return $this->isElevated($user);
    }

    public function update(User $user, BingoRoomAutomatic $bingoRoom): bool
    {
        return $this->isElevated($user) && $this->canMutate($bingoRoom);
    }

    public function delete(User $user, BingoRoomAutomatic $bingoRoom): bool
    {
        return $this->update($user, $bingoRoom);
    }

    /**
     * Alternar estado de una sala de bingo automática (autogenerable).
     */
    public function toggleStatus(User $user, BingoRoomAutomatic $bingoRoom): bool
    {
        return $this->isElevated($user) && $this->canMutate($bingoRoom);
    }
}

<?php

namespace App\Policies\Games\Bingo\Room;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para el dominio BingoRoom.
 *
 * Reglas:
 * - Solo usuarios con roles permitidos pueden operar
 * - Vendedores en estados DELETED o BLOCKED no pueden mutar
 * - Compatible con authorizeResource()
 */
class BingoRoomPolicy extends BaseRolePolicy
{
    /**
     * Roles requeridos para operar sobre vendedores.
     *
     * @return string|string[]
     */
    protected function requiredRole(): string|array
    {
        return ['superadmin', 'admin', 'commerce', 'customer'];
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
     * Puede mutar si es el creador O tiene rol privilegiado (admin/superadmin).
     */
    public function update(User $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        if (!$this->hasAccess($user) || !$this->canMutate($model)) {
            return false;
        }

        if ($user->hasRole(['superadmin', 'admin'])) {
            return true;
        }

        return (string) $user->id === (string) $model->creator_user_id;
    }

    public function delete(User $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $this->update($user, $model);
    }

    public function toggleStatus(User $user, BingoRoom $bingoRoom): bool
    {
        if (!$this->hasAccess($user) || !$this->canMutate($bingoRoom)) {
            return false;
        }

        if ($user->hasRole(['superadmin', 'admin'])) {
            return true;
        }

        return (string) $user->id === (string) $bingoRoom->creator_user_id;
    }

    public function cancel(User $user, BingoRoom $bingoRoom): bool
    {
        return $this->update($user, $bingoRoom);
    }

    public function generateDraw() {}
}

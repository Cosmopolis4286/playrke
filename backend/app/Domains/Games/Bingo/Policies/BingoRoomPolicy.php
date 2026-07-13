<?php

namespace App\Domains\Games\Bingo\Policies;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;
use Illuminate\Support\Facades\Cache;

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

    /**
     * Cualquier usuario autenticado puede ver sus propios cartones en la sala.
     * El servicio ya filtra por userId — no hay fuga de datos entre usuarios.
     */
    public function viewMyCards(User $user, BingoRoom $room): bool
    {
        return $this->hasAccess($user) || $user->hasRole('player');
    }

    /**
     * Jugadores con cartones en la sala pueden ver la lista de participantes del lobby.
     * No requiere rol de gestión — solo estar autenticado.
     */
    public function viewLobbyPlayers(User $user, BingoRoom $room): bool
    {
        // Gestores siempre pueden ver
        if ($this->hasAccess($user)) {
            return true;
        }

        // Players: solo si tienen cartones comprados en la sala
        if (!$user->hasRole('player')) {
            return false;
        }

        return \App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->where('cards_purchased', '>', 0)
            ->exists();
    }

    /**
     * Ver el estado de una partida (inGame, draw-state, checkBingo) — requiere
     * pertenecer a la sala (haber comprado cartones o ser su creador), no solo
     * tener el permiso general del módulo. Sin esto, cualquier player con
     * acceso al módulo podía consultar el sorteo/premio/ganadores de CUALQUIER
     * sala adivinando su id. Mismo criterio que viewLobbyPlayers, más el
     * fallback de creador directo que ya contempla getUserCartons() (sala
     * propia sin BingoRoomUser, ej. en dev).
     */
    public function viewGame(User $user, BingoRoom $room): bool
    {
        if ($this->hasAccess($user)) {
            return true;
        }

        if (!$user->hasRole('player')) {
            return false;
        }

        if ((string) $user->id === (string) $room->creator_user_id) {
            return true;
        }

        $cacheKey = "bingo:policy:viewGame:{$user->id}:{$room->id}";

        return Cache::remember($cacheKey, 30, fn () =>
            \App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser::where('room_id', $room->id)
                ->where('user_id', $user->id)
                ->where('cards_purchased', '>', 0)
                ->exists()
        );
    }
}

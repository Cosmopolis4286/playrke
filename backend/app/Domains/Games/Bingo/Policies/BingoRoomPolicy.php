<?php

namespace App\Domains\Games\Bingo\Policies;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Models\User;
use App\Services\Auth\RoleAuthorizationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Policy de autorización para el dominio BingoRoom.
 *
 * Reglas:
 * - Solo usuarios con roles permitidos pueden operar
 * - Salas en estados DELETED o BLOCKED no pueden mutar
 * - Compatible con authorizeResource()
 */
class BingoRoomPolicy extends BingoBasePolicy
{
    /**
     * Puede gestionar (mutar/alternar estado) si tiene rol privilegiado
     * (admin/superadmin) o si es el creador de la sala.
     */
    protected function canManage(User $user, Model $model): bool
    {
        if (!$this->hasAccess($user) || !$this->canMutate($model)) {
            return false;
        }

        if ($this->hasElevatedRole($user)) {
            return true;
        }

        return (string) $user->id === (string) $model->creator_user_id;
    }

    protected function hasElevatedRole(User $user): bool
    {
        return app(RoleAuthorizationService::class)->hasAnyRole($user, ['superadmin', 'admin']);
    }

    /**
     * Verifica pertenencia a la sala (cartones comprados > 0).
     */
    protected function belongsToRoom(User $user, BingoRoom $room): bool
    {
        return BingoRoomUser::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->where('cards_purchased', '>', 0)
            ->exists();
    }

    public function update(User $user, Model $model): bool
    {
        return $this->canManage($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->canManage($user, $model);
    }

    public function toggleStatus(User $user, BingoRoom $bingoRoom): bool
    {
        return $this->canManage($user, $bingoRoom);
    }

    public function cancel(User $user, BingoRoom $bingoRoom): bool
    {
        return $this->canManage($user, $bingoRoom);
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

        return $this->belongsToRoom($user, $room);
    }

    /**
     * Ver el estado de una partida (inGame, draw-state, checkBingo) — requiere
     * pertenecer a la sala (haber comprado cartones o ser su creador), no solo
     * tener el permiso general del módulo. Sin esto, cualquier player con
     * acceso al módulo podía consultar el sorteo/premio/ganadores de CUALQUIER
     * sala adivinando su id. Mismo criterio que viewLobbyPlayers, más el
     * fallback de creador directo que ya contempla getUserCartons() (sala
     * propia sin BingoRoomUser, ej. en dev).
     *
     * NOTA: No se bloquea por status de sala (FINISHED incluido). Los ganadores
     * deben poder consultar sus resultados después del cierre del juego.
     * El cache de 30s es seguro aquí porque cards_purchased no se revierte.
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

        return Cache::remember($cacheKey, 30, fn () => $this->belongsToRoom($user, $room));
    }
}

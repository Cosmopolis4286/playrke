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
    public function cancel(User $user, BingoRoom $bingoRoom) : bool
    {
        return $this->canManage($user, $bingoRoom);
    }

    /**
     * Crear una sala nueva — no hay $model todavía (recurso aún no existe),
     * así que no aplica el chequeo de "ser el creador" de canManage(): solo
     * rol de negocio + permiso de gestión explícito. Sobreescribe
     * BaseRolePolicy::create(), que solo miraba hasAccess() (rol).
     */
    public function create(User $user) : bool
    {
        return $this->hasAccess($user) && $this->hasManagePermission($user);
    }

    public function delete(User $user, Model $model) : bool
    {
        return $this->canManage($user, $model);
    }

    public function generateDraw()
    {
    }

    public function toggleStatus(User $user, BingoRoom $bingoRoom) : bool
    {
        return $this->canManage($user, $bingoRoom);
    }

    public function update(User $user, Model $model) : bool
    {
        return $this->canManage($user, $model);
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
    public function viewGame(User $user, BingoRoom $room) : bool
    {
        // Solo administración de plataforma tiene acceso global a las partidas.
        if ($this->hasElevatedRole($user)) {
            return true;
        }

        // El creador puede consultar su propia sala sin depender del pivote
        // BingoRoomUser (algunas salas propias no tienen ese registro).
        if ((string) $user->id === (string) $room->creator_user_id) {
            return true;
        }

        // Los permisos generales del módulo y los roles de negocio no deben
        // permitir leer el sorteo/ganadores de una sala ajena.
        if (! $user->hasRole('player')) {
            return false;
        }

        $cacheKey = "bingo:policy:viewGame:{$user->id}:{$room->id}";

        return Cache::remember($cacheKey, 30, fn () => $this->belongsToRoom($user, $room));
    }

    /**
     * Jugadores con cartones en la sala pueden ver la lista de participantes del lobby.
     * No requiere rol de gestión — solo estar autenticado.
     */
    public function viewLobbyPlayers(User $user, BingoRoom $room) : bool
    {
        // Los administradores pueden moderar cualquier sala.
        if ($this->hasElevatedRole($user)) {
            return true;
        }

        // El creador puede supervisar su propia sala, no las de otros.
        if ((string) $user->id === (string) $room->creator_user_id) {
            return true;
        }

        // Un rol de negocio (commerce/customer/subsidiary) por sí solo no
        // autoriza a inspeccionar la lista de participantes de cualquier sala.
        return $user->hasRole('player') && $this->belongsToRoom($user, $room);
    }

    /**
     * Cualquier usuario autenticado puede ver sus propios cartones en la sala.
     * El servicio ya filtra por userId — no hay fuga de datos entre usuarios.
     */
    public function viewMyCards(User $user, BingoRoom $room) : bool
    {
        return $this->hasAccess($user) || $user->hasRole('player');
    }

    /**
     * Verifica pertenencia a la sala (cartones comprados > 0).
     */
    protected function belongsToRoom(User $user, BingoRoom $room) : bool
    {
        return BingoRoomUser::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->where('cards_purchased', '>', 0)
            ->exists();
    }

    /**
     * Puede gestionar (mutar/alternar estado) si tiene rol privilegiado
     * (admin/superadmin) o si tiene el permiso de gestión Y es el creador de
     * la sala.
     *
     * Antes solo exigía hasAccess() (rol de negocio, sin distinguir permiso)
     * + ser creador — cualquier commerce/subsidiary/customer con el rol
     * correcto podía mutar SUS PROPIAS salas sin que existiera forma de
     * quitarle esa capacidad sin quitarle el rol entero. Ahora
     * hasManagePermission() (games.bingo.manage) es la puerta explícita: se
     * puede revocar por permiso sin tocar el rol, igual que el resto del
     * sistema de permisos.
     */
    protected function canManage(User $user, Model $model) : bool
    {
        if (! $this->hasAccess($user) || ! $this->hasManagePermission($user) || ! $this->canMutate($model)) {
            return false;
        }

        if ($this->hasElevatedRole($user)) {
            return true;
        }

        return (string) $user->id === (string) $model->creator_user_id;
    }

    protected function hasElevatedRole(User $user) : bool
    {
        return app(RoleAuthorizationService::class)->hasAnyRole($user, ['superadmin', 'admin']);
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Policies;

use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;
use App\Services\Auth\RoleAuthorizationService;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy de autorización para el dominio TriviaRoom.
 *
 * requiredRole() incluye 'player' porque start() lo necesita (host de la
 * sala inicia su propia partida) — por eso create/update/delete/forceClose
 * se sobrescriben explícitamente con hasElevatedRole() en vez de heredar los
 * genéricos de BaseRolePolicy tal cual, que de otro modo dejarían pasar a
 * cualquier player por culpa de ese mismo requiredRole() amplio.
 *
 * Gestión de salas (TriviaRoomAdminController::store/update/destroy/
 * forceClose) ya estaba cubierta solo por el middleware `role:admin,
 * superadmin` de routes/modules/games/trivia.php, sin ningún authorize()
 * explícito en el controller — esta Policy es la capa de defensa en
 * profundidad que faltaba.
 */
class TriviaRoomPolicy extends BaseRolePolicy
{
    /** Crear/gestionar salas: exclusivo admin/superadmin, nunca un player. */
    public function create(User $user) : bool
    {
        return $this->hasElevatedRole($user);
    }

    public function delete(User $user, Model $model) : bool
    {
        return $this->hasElevatedRole($user);
    }

    /**
     * Forzar cierre de sala — acción de dominio (no CRUD estándar), separada
     * de delete() porque cierra salas en curso (playing/waiting), no solo
     * las ya finalizadas.
     */
    public function forceClose(User $user, TriviaRoom $room) : bool
    {
        return $this->hasElevatedRole($user);
    }

    /**
     * Iniciar la partida: el host de la sala, o admin/superadmin.
     * Antes vivía inline en TriviaStartController::__invoke().
     */
    public function start(User $user, TriviaRoom $room) : bool
    {
        if ($this->hasElevatedRole($user)) {
            return true;
        }

        return (string) $user->id === (string) $room->host_id;
    }

    /**
     * Tipo del segundo parámetro forzado a Model (no TriviaRoom):
     * sobrescribe BaseRolePolicy::update(User, Model) — estrechar el tipo
     * aquí rompe PHP por contravarianza (fatal error en el bootstrap de
     * CUALQUIER request, no solo los que llaman authorize('update', ...),
     * ya confirmado en runtime dos veces en este mismo proyecto: ver
     * AdminSubsidiaryPolicy::delete() y BingoRoomAutomaticPolicy::update()).
     */
    public function update(User $user, Model $model) : bool
    {
        return $this->hasElevatedRole($user);
    }

    protected function hasElevatedRole(User $user) : bool
    {
        return app(RoleAuthorizationService::class)->hasAnyRole($user, ['superadmin', 'admin']);
    }

    protected function requiredRole() : string|array
    {
        return ['superadmin', 'admin', 'player'];
    }
}

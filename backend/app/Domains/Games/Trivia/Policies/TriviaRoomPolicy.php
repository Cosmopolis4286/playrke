<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Policies;

use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;
use App\Services\Auth\RoleAuthorizationService;

/**
 * Policy de autorización para el dominio TriviaRoom.
 *
 * Reglas de gestión (crear/editar/forzar cierre de salas) ya están cubiertas
 * por el middleware `role:admin,superadmin` en routes/modules/games/trivia.php
 * — esta policy cubre la única regla que antes vivía hardcodeada e inline en
 * un controller: iniciar una partida requiere ser el host de la sala o tener
 * rol elevado (mismo criterio que BingoRoomPolicy::canManage()).
 */
class TriviaRoomPolicy extends BaseRolePolicy
{
    protected function requiredRole(): string|array
    {
        return ['superadmin', 'admin', 'player'];
    }

    protected function hasElevatedRole(User $user): bool
    {
        return app(RoleAuthorizationService::class)->hasAnyRole($user, ['superadmin', 'admin']);
    }

    /**
     * Iniciar la partida: el host de la sala, o admin/superadmin.
     * Antes vivía inline en TriviaStartController::__invoke().
     */
    public function start(User $user, TriviaRoom $room): bool
    {
        if ($this->hasElevatedRole($user)) {
            return true;
        }

        return (string) $user->id === (string) $room->host_id;
    }
}

<?php

namespace App\Policies\Roles\Admin;

use App\Models\User;
use App\Services\Auth\RoleAuthorizationService;

/**
 * Policy de autorización para el panel admin de Players (/admin/player/*,
 * AdminPlayerController — Player = User con rol 'player', sin tabla propia).
 * Capa de defensa independiente del middleware role:admin de ruta — mismo
 * espíritu que KycPolicy/JobsCenterPolicy.
 *
 * Registrada contra su propia clase (no App\Models\User::class, ya tomada
 * por App\Policies\UserPolicy para el flujo /admins) — por eso NO extiende
 * BaseRolePolicy (sus métodos genéricos update/delete esperan (User,
 * Model), y aquí SIEMPRE se autoriza vía authorize($ability,
 * AdminPlayerPolicy::class) sin instancia, ya que no hay ownership que
 * comparar contra el $player objetivo, solo el rol del actor).
 */
class AdminPlayerPolicy
{
    private function hasAccess(User $user): bool
    {
        return app(RoleAuthorizationService::class)->hasAnyRole($user, 'admin');
    }

    public function viewAny(User $user): bool
    {
        return $this->hasAccess($user);
    }

    public function view(User $user): bool
    {
        return $this->hasAccess($user);
    }

    public function create(User $user): bool
    {
        return $this->hasAccess($user);
    }

    public function update(User $user): bool
    {
        return $this->hasAccess($user);
    }

    public function delete(User $user): bool
    {
        return $this->hasAccess($user);
    }

    public function toggleStatus(User $user): bool
    {
        return $this->hasAccess($user);
    }

    public function resetPassword(User $user): bool
    {
        return $this->hasAccess($user);
    }
}

<?php

namespace App\Policies\Roles\Admin;

use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\User;
use App\Services\Auth\RoleAuthorizationService;

/**
 * Policy de autorización para la administración de Jugadores Externos
 * (compradores de cartones de Bingo sin cuenta, ver App\Services\Roles\Player\External\ExternalPlayerLinkService).
 *
 * A diferencia del resto de policies de Roles/Admin, NO extiende BaseRolePolicy
 * (que autoriza por rol fijo): usa permisos granulares `external-players.*`
 * (ver PermissionSeeder/RolePermissionSeeder) para que admin/superadmin sigan
 * teniendo acceso por defecto, pero también sea otorgable a commerce/seller
 * desde /admin/permissions sin tocar código.
 */
class ExternalPlayerPolicy
{
    private function hasPermission(User $user, string $permission): bool
    {
        return app(RoleAuthorizationService::class)->hasAnyPermission($user, $permission);
    }

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'external-players.view');
    }

    public function view(User $user, ExternalPlayer $externalPlayer): bool
    {
        return $this->hasPermission($user, 'external-players.view');
    }

    public function fetchList(User $user): bool
    {
        return $this->hasPermission($user, 'external-players.view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'external-players.create');
    }

    public function export(User $user): bool
    {
        return $this->hasPermission($user, 'external-players.export');
    }

    public function update(User $user, ExternalPlayer $externalPlayer): bool
    {
        return $this->hasPermission($user, 'external-players.manage');
    }

    public function toggleBlock(User $user, ExternalPlayer $externalPlayer): bool
    {
        return $this->hasPermission($user, 'external-players.block');
    }
}

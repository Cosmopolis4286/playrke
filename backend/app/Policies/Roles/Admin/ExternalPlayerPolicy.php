<?php

namespace App\Policies\Roles\Admin;

use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para la administración de Jugadores Externos
 * (compradores de cartones de Bingo sin cuenta, ver App\Traits\Users\PlayerLinkTrait).
 *
 * admin y superadmin: mismo nivel que el resto de paneles administrativos
 * transversales (ver PermissionPolicy para el caso solo-superadmin).
 */
class ExternalPlayerPolicy extends BaseRolePolicy
{
    protected function requiredRole(): string|array
    {
        return ['admin', 'superadmin'];
    }
}

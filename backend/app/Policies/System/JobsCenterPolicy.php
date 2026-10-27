<?php

namespace App\Policies\System;

use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para el panel "Jobs y Scheduler" (colas, failed
 * jobs, comandos programados). Capa de defensa independiente del middleware
 * `multiPermission:system.jobs` de ruta — mismo espíritu documentado en
 * PermissionPolicy: si el middleware de la ruta cambia, esta Policy sigue
 * exigiendo superadmin por su cuenta.
 *
 * El recurso no es un modelo Eloquent único (es una vista compuesta sobre
 * scheduled_commands + jobs + failed_jobs), así que no hay un Model natural
 * para update()/delete() de BaseRolePolicy — viewAny() (heredado) cubre "ver
 * la página", manage() cubre las acciones mutables (toggle scheduler,
 * retry/forget jobs), mismo patrón que PermissionPolicy::manageAssignments().
 */
class JobsCenterPolicy extends BaseRolePolicy
{
    public function manage(User $user) : bool
    {
        return $this->hasAccess($user);
    }

    protected function requiredRole() : string|array
    {
        return 'superadmin';
    }
}

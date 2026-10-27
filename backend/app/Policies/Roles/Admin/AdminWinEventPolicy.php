<?php

namespace App\Policies\Roles\Admin;

use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para el panel admin de registro manual de eventos
 * de ganancia (/admin/win-event/*, AdminWinEventController::store() —
 * registra montos reales pagados fuera de la plataforma). Capa de defensa
 * independiente del middleware role:superadmin,admin de ruta.
 */
class AdminWinEventPolicy extends BaseRolePolicy
{
    protected function requiredRole() : string|array
    {
        return ['superadmin', 'admin'];
    }
}

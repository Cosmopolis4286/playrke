<?php

namespace App\Policies\Roles\Admin;

use App\Models\User;

/**
 * Policy de autorización para la gestión de visibilidad de widgets del
 * dashboard por rol.
 *
 * Capa de defensa independiente del middleware `multiPermission:
 * dashboard-widgets.view,dashboard-widgets.manage` de ruta — mismo patrón
 * que SidebarMenuVisibilityPolicy.
 *
 * Permission-based (no BaseRolePolicy con rol fijo): antes exigía
 * role:superadmin puro; migrado a dashboard-widgets.view/
 * dashboard-widgets.manage (ver migración
 * 2026_09_27_000001_add_sidebar_and_dashboard_visibility_permissions) —
 * hoy solo 'superadmin' tiene esos permisos (mismo nivel de exclusividad
 * de antes, por diseño), pero el mecanismo ya es permission-based para
 * permitir delegar a 'admin' en el futuro sin tocar código.
 *
 * manage() (no update() heredado de BaseRolePolicy) para guardar cambios:
 * saveChanges() aplica un LOTE de cambios sobre múltiples filas
 * (item_key × role_id), sin un modelo único que autorizar.
 */
class DashboardWidgetVisibilityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['dashboard-widgets.view', 'dashboard-widgets.manage']);
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('dashboard-widgets.manage');
    }
}

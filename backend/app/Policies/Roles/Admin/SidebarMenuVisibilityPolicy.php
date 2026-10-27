<?php

namespace App\Policies\Roles\Admin;

use App\Models\User;

/**
 * Policy de autorización para la gestión de visibilidad del sidebar por rol.
 *
 * Capa de defensa independiente del middleware `multiPermission:
 * sidebar-menu.view,sidebar-menu.manage` de ruta — si el middleware de la
 * ruta cambia, esta Policy sigue exigiendo el permiso correcto por su
 * cuenta (mismo espíritu que KycPolicy/JobsCenterPolicy).
 *
 * Permission-based (no BaseRolePolicy con rol fijo): antes exigía
 * role:superadmin puro; migrado a sidebar-menu.view/sidebar-menu.manage
 * (ver migración 2026_09_27_000001_add_sidebar_and_dashboard_visibility_
 * permissions) — hoy solo 'superadmin' tiene esos permisos (mismo nivel de
 * exclusividad de antes, por diseño: permissions/modules/roles-admin se
 * quedan deliberadamente fuera de esta migración), pero el mecanismo ya es
 * permission-based para permitir delegar a 'admin' en el futuro sin tocar
 * código, solo el seeder/una migración de datos nueva.
 *
 * manage() (no update() heredado de BaseRolePolicy) para guardar cambios:
 * saveChanges() aplica un LOTE de cambios sobre múltiples filas
 * (item_key × role_id), sin un modelo único que autorizar.
 */
class SidebarMenuVisibilityPolicy
{
    public function manage(User $user) : bool
    {
        return $user->hasPermissionTo('sidebar-menu.manage');
    }

    public function view(User $user) : bool
    {
        return $this->viewAny($user);
    }

    public function viewAny(User $user) : bool
    {
        return $user->hasAnyPermission(['sidebar-menu.view', 'sidebar-menu.manage']);
    }
}

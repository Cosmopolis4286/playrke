<?php

namespace App\Policies\Roles\Admin;

use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para la gestión de visibilidad del sidebar por rol.
 *
 * Capa de defensa independiente del middleware `role:superadmin` de ruta
 * (`role_protected_routes()` en routes/modules/admin.php) — mismo patrón que
 * ModulePolicy/PermissionPolicy. Cambiar qué ve cada rol en el sidebar es
 * una decisión de acceso equivalente a Permission, así que exige el mismo
 * rol base.
 */
class SidebarMenuVisibilityPolicy extends BaseRolePolicy
{
    protected function requiredRole(): string|array
    {
        return 'superadmin';
    }
}

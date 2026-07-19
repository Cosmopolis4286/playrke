<?php

namespace App\Policies\Roles\Admin;

use App\Models\Permission;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para la gestión de Permisos.
 *
 * Capa de defensa independiente del middleware `role:superadmin` de ruta
 * (`role_protected_routes()` en routes/modules/admin.php): si algún día el
 * middleware de esa ruta cambia o se reutiliza el controlador en otro
 * contexto, esta Policy sigue exigiendo superadmin por su cuenta.
 *
 * update/delete además bloquean permisos de sistema (is_system=true) —
 * reemplaza la protección puramente visual que antes vivía solo en
 * PermissionTable.vue::isSystem() (una regex de frontend, sin respaldo real
 * en el backend).
 */
class PermissionPolicy extends BaseRolePolicy
{
    protected function requiredRole(): string|array
    {
        return 'superadmin';
    }

    public function update(User $user, Permission $permission): bool
    {
        return $this->hasAccess($user) && ! $permission->is_system;
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $this->hasAccess($user) && ! $permission->is_system;
    }
}

<?php

namespace App\Policies\Roles\Admin;

use App\Models\Permission;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;
use Illuminate\Database\Eloquent\Model;

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
    public function delete(User $user, Model $model) : bool
    {
        return $this->hasAccess($user) && ! $model->is_system;
    }

    /**
     * Asignar/revocar permisos a un rol opera sobre un lote de IDs, no sobre
     * una instancia única de Permission — no hay un Model natural que pasarle
     * a update()/delete(). Aquí solo se exige el rol base (superadmin); el
     * filtrado de permisos is_system dentro del lote se hace en
     * PermissionService::assignPermissionsToRole()/revokePermissionsFromRole().
     */
    public function manageAssignments(User $user) : bool
    {
        return $this->hasAccess($user);
    }

    /**
     * @param Permission $model tipado como Model (no Permission) para mantener
     *                          la firma compatible con BaseRolePolicy::update() — PHP no permite
     *                          estrechar el tipo de un parámetro en un override
     */
    public function update(User $user, Model $model) : bool
    {
        return $this->hasAccess($user) && ! $model->is_system;
    }

    protected function requiredRole() : string|array
    {
        return 'superadmin';
    }
}

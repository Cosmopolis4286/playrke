<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy de autorización sobre el modelo User en operaciones administrativas
 * transversales (no ligadas a un dominio de rol específico como Commerce/Seller).
 *
 * Capa de defensa independiente del middleware `role:superadmin` de ruta
 * (`role_protected_routes()` en routes/modules/admin.php) — mismo patrón que
 * PermissionPolicy: si el middleware de la ruta cambia o el controlador se
 * reutiliza en otro contexto, esta Policy sigue exigiendo superadmin por su cuenta.
 */
class UserPolicy extends BaseRolePolicy
{
    protected function requiredRole(): string|array
    {
        return 'superadmin';
    }

    /**
     * Ver/editar los permisos específicos (specific_permissions) de otro
     * usuario. Sin restricción adicional por rol del usuario objetivo —
     * decisión de producto: aplica a cualquier usuario del sistema, incluidos
     * otros admin/superadmin.
     *
     * @param Model $targetUser Tipado como Model (no User) para mantener la
     *   firma compatible con el resto de métodos de BaseRolePolicy.
     */
    public function managePermissions(User $actingUser, Model $targetUser): bool
    {
        return $this->hasAccess($actingUser);
    }
}

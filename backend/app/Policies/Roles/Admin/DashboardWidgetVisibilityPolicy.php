<?php

namespace App\Policies\Roles\Admin;

use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para la gestión de visibilidad de widgets del
 * dashboard por rol.
 *
 * Capa de defensa independiente del middleware `role:superadmin` de ruta
 * (`role_protected_routes()` en routes/modules/admin.php) — mismo patrón que
 * SidebarMenuVisibilityPolicy. Cambiar qué stats/accesos ve cada rol es una
 * decisión de acceso equivalente a Permission, así que exige el mismo rol
 * base.
 *
 * manage() (no update() heredado de BaseRolePolicy) para guardar cambios:
 * update(User, Model) exige una instancia real de modelo, pero
 * saveChanges() aplica un LOTE de cambios sobre múltiples filas
 * (item_key × role_id), sin un modelo único que autorizar — mismo criterio
 * que JobsCenterPolicy::manage()/KycPolicy::manage() para acciones mutables
 * sin modelo específico. Usar authorize('update', self::class) aquí
 * lanzaría "Too few arguments... exactly 2 expected", porque Gate::authorize
 * invoca update() pasando solo el nombre de clase (sin instancia) cuando el
 * segundo argumento no puede resolverse implícitamente.
 */
class DashboardWidgetVisibilityPolicy extends BaseRolePolicy
{
    protected function requiredRole(): string|array
    {
        return 'superadmin';
    }

    public function manage(User $user): bool
    {
        return $this->hasAccess($user);
    }
}

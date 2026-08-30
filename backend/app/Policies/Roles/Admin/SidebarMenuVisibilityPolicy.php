<?php

namespace App\Policies\Roles\Admin;

use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para la gestión de visibilidad del sidebar por rol.
 *
 * Capa de defensa independiente del middleware `role:superadmin` de ruta
 * (`role_protected_routes()` en routes/modules/admin.php) — mismo patrón que
 * ModulePolicy/PermissionPolicy. Cambiar qué ve cada rol en el sidebar es
 * una decisión de acceso equivalente a Permission, así que exige el mismo
 * rol base.
 *
 * manage() (no update() heredado de BaseRolePolicy) para guardar cambios:
 * update(User, Model) exige una instancia real de modelo, pero
 * saveChanges() aplica un LOTE de cambios sobre múltiples filas
 * (item_key × role_id), sin un modelo único que autorizar. El controller
 * usaba authorize('update', self::class) — eso lanza "Too few arguments...
 * exactly 2 expected" en cuanto se ejercita de verdad (confirmado real,
 * no solo teórico), porque Gate::authorize invoca update() pasando solo el
 * nombre de clase cuando no hay instancia que resolver implícitamente.
 */
class SidebarMenuVisibilityPolicy extends BaseRolePolicy
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

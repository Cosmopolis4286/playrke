<?php

namespace App\Policies\Roles\Admin;

use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para la gestión de Módulos del sistema.
 *
 * Capa de defensa independiente del middleware `role:superadmin` de ruta
 * (`role_protected_routes()` en routes/modules/admin.php) — mismo patrón que
 * PermissionPolicy. Apagar un módulo afecta a TODOS los usuarios (incluido
 * superadmin), así que solo superadmin puede tocarlo.
 */
class ModulePolicy extends BaseRolePolicy
{
    protected function requiredRole() : string|array
    {
        return 'superadmin';
    }
}

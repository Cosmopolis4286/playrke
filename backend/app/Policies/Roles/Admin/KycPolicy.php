<?php

namespace App\Policies\Roles\Admin;

use App\Models\User;

/**
 * Policy de autorización para el panel admin de KYC (cola de revisión de
 * documentos). Capa de defensa independiente del middleware
 * `multiPermission:kyc.view,kyc.manage` de ruta — si el middleware de la
 * ruta cambia, esta Policy sigue exigiendo el permiso correcto por su
 * cuenta (mismo espíritu que JobsCenterPolicy/PermissionPolicy).
 *
 * A diferencia de BaseRolePolicy (rol fijo), KYC es permission-based: lo
 * usan tanto 'admin' como 'superadmin' vía permisos Spatie explícitos
 * (kyc.view/kyc.manage en RolePermissionSeeder), no un rol dedicado —
 * de ahí que esta policy consulte los permisos directamente en vez de
 * heredar de BaseRolePolicy.
 *
 * view/viewAny cubren listar y ver detalle de documentos; manage cubre
 * aprobar/rechazar (acción mutable, requiere el permiso más estricto).
 */
class KycPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['kyc.view', 'kyc.manage']);
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('kyc.manage');
    }
}

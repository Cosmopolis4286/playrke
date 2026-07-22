<?php

namespace App\Services\Roles\Admin\User;

use App\Models\Permission;
use App\Models\User;
use App\Services\Roles\Admin\PermissionService;

/**
 * Gestión de permisos específicos por usuario (users.specific_permissions).
 *
 * Distinto de PermissionService: aquél gestiona la matriz rol×permiso
 * (role_has_permissions); este gestiona los permisos directos de UN usuario,
 * que se suman a los heredados por su rol (ver User::hasPermissionTo()).
 */
final class UserPermissionService
{
    public function __construct(
        private readonly PermissionService $permissionService,
    ) {}

    /**
     * Permisos del usuario, distinguiendo origen: heredados por rol
     * (via_roles, de solo lectura aquí) vs. específicos otorgados
     * directamente (specific, editables), más el catálogo completo de
     * permisos disponibles en el sistema para poblar el selector.
     *
     * @return array{via_roles: array<int,string>, specific: array<int,string>, catalog: array}
     */
    public function getUserPermissions(User $user): array
    {
        return [
            'via_roles' => $user->getAllPermissions()->pluck('name')->all(),
            'specific'  => $user->specific_permissions ?? [],
            'catalog'   => $this->permissionService->getAllForMatrix(),
        ];
    }

    /**
     * Reemplaza el set completo de permisos específicos del usuario por el
     * enviado desde el frontend (checkboxes de un modal, no un patrón de
     * "pending changes" por separado como en la matriz de roles — aquí basta
     * con diffear contra el estado actual y aplicar altas/bajas).
     *
     * Excluye is_system=true por el mismo motivo que
     * PermissionService::assignPermissionsToRole()/revokePermissionsFromRole():
     * el checkbox deshabilitado en el frontend es solo cosmético, este filtro
     * es el único punto real que impide otorgar/revocar permisos estructurales
     * del sistema directamente a un usuario.
     *
     * @param string[] $permissionNames Nombres de permiso (no IDs — specific_permissions
     *   guarda strings, igual que ya lo hacen los 3 servicios de activación de juegos).
     */
    public function syncSpecificPermissions(User $user, array $permissionNames): void
    {
        $systemPermissionNames = Permission::where('is_system', true)->pluck('name')->all();

        $current       = $user->specific_permissions ?? [];
        $requestedSafe = array_diff($permissionNames, $systemPermissionNames);

        $toRevoke = array_diff($current, $requestedSafe, $systemPermissionNames);
        $toGrant  = array_diff($requestedSafe, $current);

        if ($toRevoke !== []) {
            $user->revokeSpecificPermission(array_values($toRevoke));
        }
        if ($toGrant !== []) {
            $user->giveSpecificPermission(array_values($toGrant));
        }
    }
}

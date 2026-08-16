<?php

namespace App\Services\Roles\Admin\User;

use App\Models\Audit;
use App\Models\Permission;
use App\Models\User;
use App\Services\Roles\Admin\PermissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

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
     * Una sola escritura ($user->update(), no giveSpecificPermission() +
     * revokeSpecificPermission() por separado) para que User::Auditable
     * registre un único evento 'updated' por sync, no dos. El $reason opcional
     * queda en un evento de auditoría MANUAL aparte (ver logGrantReason) —
     * Audit no tiene columna de metadata libre, así que no cabe en el diff
     * automático de columnas que ya genera Auditable.
     *
     * @param string[] $permissionNames Nombres de permiso (no IDs — specific_permissions
     *   guarda strings, igual que ya lo hacen los 3 servicios de activación de juegos).
     */
    public function syncSpecificPermissions(User $user, array $permissionNames, ?string $reason = null): void
    {
        $systemPermissionNames = Permission::where('is_system', true)->pluck('name')->all();

        $current       = $user->specific_permissions ?? [];
        $requestedSafe = array_values(array_diff($permissionNames, $systemPermissionNames));

        $toRevoke = array_diff($current, $requestedSafe, $systemPermissionNames);
        $toGrant  = array_diff($requestedSafe, $current);

        if ($toRevoke === [] && $toGrant === []) {
            return;
        }

        $user->update(['specific_permissions' => $requestedSafe]);

        if ($reason !== null && trim($reason) !== '') {
            $this->logSyncReason($user, $toGrant, $toRevoke, trim($reason));
        }
    }

    /**
     * Evento de auditoría manual con el motivo declarado por el admin al
     * otorgar/revocar permisos específicos — separado del evento 'updated'
     * automático de User (que ya cubre el diff old/new de specific_permissions)
     * porque el motivo es información de negocio, no un valor de columna.
     */
    private function logSyncReason(User $user, array $granted, array $revoked, string $reason): void
    {
        Audit::create([
            'user_id'        => Auth::id(),
            'actor_type'     => Auth::check() ? 'user' : 'system',
            'auditable_type' => User::class,
            'auditable_id'   => (string) $user->getKey(),
            'event'          => 'specific_permissions_synced',
            // Sin json_encode() manual: old_values/new_values ya tienen cast
            // 'array' en Audit, Eloquent se encarga de (de)serializar — igual
            // que Auditable::auditChanges() (ver ese trait para el porqué).
            'old_values'     => ['revoked' => array_values($revoked)],
            'new_values'     => ['granted' => array_values($granted), 'reason' => $reason],
            'url'            => Request::instance()?->fullUrl(),
            'ip_address'     => Request::instance()?->ip(),
            'user_agent'     => Request::instance()?->userAgent(),
        ]);
    }
}

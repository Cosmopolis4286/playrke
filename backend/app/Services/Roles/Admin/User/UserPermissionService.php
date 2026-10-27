<?php

namespace App\Services\Roles\Admin\User;

use App\Models\Audit;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserSpecificPermissionGrant;
use App\Services\Roles\Admin\PermissionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Gestión de permisos específicos por usuario (user_specific_permission_grants).
 *
 * Distinto de PermissionService: aquél gestiona la matriz rol×permiso
 * (role_has_permissions); este gestiona los permisos directos de UN usuario,
 * que se suman a los heredados por su rol (ver User::hasPermissionTo()).
 */
final class UserPermissionService
{
    public function __construct(
        private readonly PermissionService $permissionService,
    ) {
    }

    /**
     * Permisos del usuario, distinguiendo origen: heredados por rol
     * (via_roles, de solo lectura aquí) vs. específicos otorgados
     * directamente (specific, editables, con su expiración), más el
     * catálogo completo de permisos disponibles para poblar el selector.
     *
     * @return array{via_roles: array<int,string>, specific: array<int,array{name: string, expires_at: ?string, reason: ?string}>, catalog: array}
     */
    public function getUserPermissions(User $user) : array
    {
        $specific = $user->specificPermissionGrants()
            ->active()
            ->orderBy('permission_name')
            ->get()
            ->map(fn (UserSpecificPermissionGrant $grant) => [
                'name' => $grant->permission_name,
                'expires_at' => $grant->expires_at?->toISOString(),
                'reason' => $grant->reason,
            ])
            ->values()
            ->all();

        return [
            'via_roles' => $user->getAllPermissions()->pluck('name')->all(),
            'specific' => $specific,
            'catalog' => $this->permissionService->getAllForMatrix(),
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
     * Cada fila de grant/revoke queda auditada automáticamente por
     * UserSpecificPermissionGrant::Auditable (eventos created/updated) — el
     * $reason opcional del formulario, en cambio, no tiene columna propia en
     * Audit para "motivo de negocio de la operación completa", así que se
     * registra en un evento manual aparte (ver logSyncReason).
     *
     * @param array<int, array{name: string, expires_at?: string|null}> $permissions
     */
    public function syncSpecificPermissions(User $user, array $permissions, ?string $reason = null) : void
    {
        $systemPermissionNames = Permission::where('is_system', true)->pluck('name')->all();

        $requested = collect($permissions)
            ->reject(fn (array $p) => in_array($p['name'], $systemPermissionNames, true))
            ->keyBy('name');

        $current = $user->specificPermissionGrants()->active()->get()->keyBy('permission_name');

        $toRevoke = $current->keys()->diff($requested->keys())->values()->all();
        $toGrant = $requested->keys()->diff($current->keys())->values()->all();

        // Permisos presentes en ambos: si cambió la expiración, se actualiza
        // in-place (no se trata como revoke+grant, para no perder el
        // granted_by/created_at original del grant).
        $toUpdateExpiry = $requested->keys()->intersect($current->keys())
            ->filter(function(string $name) use ($requested, $current) {
                $newExpiresAt = $requested[$name]['expires_at'] ?? null;
                $currentExpiresAt = $current[$name]->expires_at?->toISOString();

                return $newExpiresAt !== $currentExpiresAt;
            })
            ->values();

        if ($toRevoke === [] && $toGrant === [] && $toUpdateExpiry->isEmpty()) {
            return;
        }

        if ($toRevoke !== []) {
            $user->specificPermissionGrants()
                ->active()
                ->whereIn('permission_name', $toRevoke)
                ->update(['revoked_at' => now(), 'revoked_reason' => UserSpecificPermissionGrant::REVOKED_REASON_MANUAL]);
        }

        foreach ($toGrant as $name) {
            $expiresAt = $requested[$name]['expires_at'] ?? null;
            $user->specificPermissionGrants()->create([
                'permission_name' => $name,
                'granted_by' => Auth::id(),
                'reason' => $reason,
                'expires_at' => $expiresAt ? Carbon::parse($expiresAt) : null,
            ]);
        }

        foreach ($toUpdateExpiry as $name) {
            $expiresAt = $requested[$name]['expires_at'] ?? null;
            $current[$name]->update([
                'expires_at' => $expiresAt ? Carbon::parse($expiresAt) : null,
                'expiring_notified_at' => null, // nueva fecha: el aviso previo debe poder reenviarse
            ]);
        }

        if ($reason !== null && trim($reason) !== '') {
            $this->logSyncReason($user, $toGrant, $toRevoke, trim($reason));
        }
    }

    /**
     * Evento de auditoría manual con el motivo declarado por el admin al
     * otorgar/revocar permisos específicos — separado de los eventos
     * 'created'/'updated' automáticos de UserSpecificPermissionGrant::Auditable
     * (que ya cubren el diff old/new de cada fila) porque el motivo es
     * información de negocio de la operación completa, no un valor de columna.
     */
    private function logSyncReason(User $user, array $granted, array $revoked, string $reason) : void
    {
        Audit::create([
            'user_id' => Auth::id(),
            'actor_type' => Auth::check() ? 'user' : 'system',
            'auditable_type' => User::class,
            'auditable_id' => (string) $user->getKey(),
            'event' => 'specific_permissions_synced',
            // Sin json_encode() manual: old_values/new_values ya tienen cast
            // 'array' en Audit, Eloquent se encarga de (de)serializar — igual
            // que Auditable::auditChanges() (ver ese trait para el porqué).
            'old_values' => ['revoked' => array_values($revoked)],
            'new_values' => ['granted' => array_values($granted), 'reason' => $reason],
            'url' => Request::instance()?->fullUrl(),
            'ip_address' => Request::instance()?->ip(),
            'user_agent' => Request::instance()?->userAgent(),
        ]);
    }
}

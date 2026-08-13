<?php

namespace App\Services\Roles\Admin;

use App\DTOs\Roles\Admin\Permission\PermissionDTO;
use App\DTOs\Roles\Admin\Permission\RolePermissionDTO;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;

final class PermissionService
{
    /**
     * Obtener todos los permisos con paginación.
     */
    /**
     * Todos los permisos para la matriz de roles (sin paginación).
     * toArray() aplica los casts del modelo — 'id' => 'string' garantiza
     * que el Snowflake ID no pierda precisión en JavaScript.
     */
    public function getAllForMatrix(): array
    {
        return Permission::orderBy('name')->get()->toArray();
    }

    /**
     * Obtener todos los permisos.
     */
    public function getAllPermissions(): Collection
    {
        return Permission::orderBy('name')->get();
    }

    /**
     * Obtener permisos por rol.
     */
    public function getPermissionsByRole(string $roleId): RolePermissionDTO
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        
        return new RolePermissionDTO(
            roleId: $role->id,
            roleName: $role->name,
            rolePrefix: $role->prefix,
            permissions: $role->permissions->map(fn($permission) => new PermissionDTO(
                id: $permission->id,
                name: $permission->name,
                guardName: $permission->guard_name,
                description: $permission->description,
                status: true,
                createdAt: $permission->created_at?->format('Y-m-d H:i:s'),
                updatedAt: $permission->updated_at?->format('Y-m-d H:i:s'),
            ))->toArray(),
        );
    }

    /**
     * Asignar permisos a un rol.
     *
     * Resuelve los IDs a instancias Permission antes de pasarlas a Spatie:
     * givePermissionTo() acepta arrays, pero internamente busca por *nombre*
     * cuando recibe un array de strings (Spatie\Permission\Traits\HasPermissions::
     * getStoredPermission()) — pasar Snowflake IDs directamente no encontraría
     * ninguna coincidencia. Pasar la Collection de instancias evita esa ambigüedad
     * y además deja que Spatie invalide la cache de permisos automáticamente
     * (Role usa RefreshesPermissionCache), sin necesidad de limpiarla a mano.
     *
     * Excluye is_system=true: el checkbox deshabilitado en el frontend es
     * solo cosmético, así que este filtro es el único punto real que impide
     * reasignar permisos estructurales del sistema vía el endpoint de lote.
     */
    public function assignPermissionsToRole(string $roleId, array $permissionIds): void
    {
        $role = Role::findOrFail($roleId);

        $permissions = Permission::whereIn('id', $permissionIds)
            ->where('is_system', false)
            ->get();

        if ($permissions->isNotEmpty()) {
            $role->givePermissionTo($permissions);
        }
    }

    /**
     * Revocar permisos de un rol.
     *
     * Excluye is_system=true por el mismo motivo que assignPermissionsToRole().
     *
     * superadmin nunca pierde permisos por esta vía: es el rol raíz de
     * autorización (ver RoleAuthorizationService::isSuperadmin) y debe
     * conservar el 100% de los permisos del guard 'web' en todo momento —
     * el checkbox deshabilitado en el frontend es solo cosmético, este
     * guard es el que realmente lo impide.
     */
    public function revokePermissionsFromRole(string $roleId, array $permissionIds): void
    {
        $role = Role::findOrFail($roleId);

        if ($role->name === 'superadmin') {
            return;
        }

        $permissions = Permission::whereIn('id', $permissionIds)
            ->where('is_system', false)
            ->get();

        foreach ($permissions as $permission) {
            $role->revokePermissionTo($permission);
        }
    }

    /**
     * Crear un nuevo permiso.
     *
     * Se asigna automáticamente a superadmin si comparte su guard: superadmin
     * debe tener el 100% de los permisos disponibles en todo momento (no solo
     * en el seed inicial), así que un permiso nuevo no puede quedar fuera de
     * su rol raíz esperando una asignación manual desde la matriz.
     */
    public function createPermission(array $data): PermissionDTO
    {
        $permission = Permission::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
            'description' => $data['description'] ?? null,
        ]);

        $superadmin = Role::where('name', 'superadmin')
            ->where('guard_name', $permission->guard_name)
            ->first();

        if ($superadmin) {
            $superadmin->givePermissionTo($permission);
        }

        return new PermissionDTO(
            id: $permission->id,
            name: $permission->name,
            guardName: $permission->guard_name,
            description: $permission->description,
            status: true,
            createdAt: $permission->created_at->format('Y-m-d H:i:s'),
            updatedAt: $permission->updated_at->format('Y-m-d H:i:s'),
        );
    }

    /**
     * Actualizar un permiso.
     */
    public function updatePermission(string $permissionId, array $data): PermissionDTO
    {
        $permission = Permission::findOrFail($permissionId);
        
        $permission->update([
            'name' => $data['name'] ?? $permission->name,
            'guard_name' => $data['guard_name'] ?? $permission->guard_name,
            'description' => $data['description'] ?? $permission->description,
        ]);

        return new PermissionDTO(
            id: $permission->id,
            name: $permission->name,
            guardName: $permission->guard_name,
            description: $permission->description,
            status: true,
            createdAt: $permission->created_at->format('Y-m-d H:i:s'),
            updatedAt: $permission->updated_at->format('Y-m-d H:i:s'),
        );
    }

    /**
     * Eliminar un permiso.
     *
     * La verificación de is_system vive en PermissionPolicy (autorización),
     * no aquí — este método asume que ya se autorizó la operación.
     * Invalida la cache de Spatie explícitamente: a diferencia de
     * assign/revoke (que la limpian solos porque pasan por Role, que usa
     * RefreshesPermissionCache), borrar el Permission en sí no dispara esa
     * limpieza automáticamente.
     */
    public function deletePermission(string $permissionId): void
    {
        $permission = Permission::findOrFail($permissionId);
        $permission->delete();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Aplicar en una sola transacción todos los cambios pendientes de la
     * matriz (varios roles a la vez). Reemplaza el patrón anterior de N
     * requests assign/revoke en paralelo desde el frontend, que podía dejar
     * el estado a medias si una de esas requests fallaba.
     *
     * $changes: array de ['role_id' => string, 'permission_id' => string, 'granted' => bool]
     */
    public function applyMatrixChanges(array $changes): void
    {
        DB::transaction(function () use ($changes) {
            foreach (collect($changes)->groupBy('role_id') as $roleId => $roleChanges) {
                $role = Role::findOrFail($roleId);

                $toGrantIds = $roleChanges->where('granted', true)->pluck('permission_id');
                $toRevokeIds = $role->name === 'superadmin'
                    ? collect()
                    : $roleChanges->where('granted', false)->pluck('permission_id');

                $toGrant = Permission::whereIn('id', $toGrantIds)->where('is_system', false)->get();
                $toRevoke = Permission::whereIn('id', $toRevokeIds)->where('is_system', false)->get();

                if ($toGrant->isNotEmpty()) {
                    $role->givePermissionTo($toGrant);
                }

                foreach ($toRevoke as $permission) {
                    $role->revokePermissionTo($permission);
                }
            }
        });
    }

    /**
     * $replace = false (default): merge, solo agrega los permisos del origen
     * sin quitarle nada al destino que ya tenía. $replace = true: el destino
     * termina con EXACTAMENTE los permisos no-sistema del origen — sus
     * permisos no-sistema previos que no estén en el origen se revocan. Los
     * permisos de sistema del destino nunca se tocan en ningún modo.
     */
    public function copyPermissionsBetweenRoles(string $sourceRoleId, string $targetRoleId, bool $replace = false): int
    {
        return DB::transaction(function () use ($sourceRoleId, $targetRoleId, $replace) {
            $source = Role::with('permissions')->findOrFail($sourceRoleId);
            $target = Role::with('permissions')->findOrFail($targetRoleId);

            $permissions = $source->permissions->where('is_system', false);

            if ($replace) {
                $systemPermissions = $target->permissions->where('is_system', true);
                $target->syncPermissions($systemPermissions->merge($permissions));
            } elseif ($permissions->isNotEmpty()) {
                $target->givePermissionTo($permissions);
            }

            return $permissions->count();
        });
    }

    /**
     * Obtener roles con sus permisos.
     */
    public function getRolesWithPermissions(): array
    {
        return Role::with('permissions')
            ->where('status', true)
            ->orderBy('name')
            ->get()
            ->map(fn(Role $role) => [
                'id'          => (string) $role->id,
                'name'        => $role->name,
                'prefix'      => $role->prefix ?? $role->name,
                'permissions' => $role->permissions->pluck('id')->map(fn($id) => (string) $id)->toArray(),
            ])
            ->toArray();
    }
}

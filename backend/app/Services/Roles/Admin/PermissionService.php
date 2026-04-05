<?php

namespace App\Services\Roles\Admin;

use App\DTOs\Roles\Admin\Permission\PermissionDTO;
use App\DTOs\Roles\Admin\Permission\RolePermissionDTO;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

final class PermissionService
{
    /**
     * Obtener todos los permisos con paginación.
     */
    public function getPermissionsWithPagination(int $perPage = 15): array
    {
        $permissions = Permission::with('roles')
            ->orderBy('name')
            ->paginate($perPage);

        // Convertir a arrays con IDs como strings para evitar pérdida de precisión en JS
        $items = collect($permissions->items())->map(fn($p) => [
            'id' => (string) $p->id,
            'name' => $p->name,
            'guard_name' => $p->guard_name,
            'description' => $p->description,
            'created_at' => $p->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $p->updated_at?->format('Y-m-d H:i:s'),
            'roles' => $p->roles->map(fn($r) => [
                'id' => (string) $r->id,
                'name' => $r->name,
                'guard_name' => $r->guard_name,
            ])->toArray(),
        ])->toArray();

        return [
            'permissions' => $items,
            'pagination' => [
                'current_page' => $permissions->currentPage(),
                'per_page' => $permissions->perPage(),
                'total' => $permissions->total(),
                'last_page' => $permissions->lastPage(),
                'from' => $permissions->firstItem(),
                'to' => $permissions->lastItem(),
            ],
        ];
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
     */
    public function assignPermissionsToRole(string $roleId, array $permissionIds): void
    {
        \Log::debug('=== ASSIGN PERMISSIONS START ===', [
            'role_id' => $roleId,
            'permission_ids' => $permissionIds,
        ]);

        // Los IDs se mantienen como strings para compatibilidad con Snowflake
        $roleIdStr = (string) $roleId;
        $permissionIdsStr = array_map(fn($id) => (string) $id, $permissionIds);

        \Log::debug('Converted to strings', [
            'role_id_str' => $roleIdStr,
            'permission_ids_str' => $permissionIdsStr,
        ]);

        // Verificar que el rol existe
        $roleExists = DB::table('roles')->where('id', $roleIdStr)->exists();
        \Log::debug('Role exists check', ['role_id' => $roleIdStr, 'exists' => $roleExists]);

        if (!$roleExists) {
            throw new \Exception("Rol no encontrado con ID: {$roleId}");
        }

        // Verificar que los permisos existen
        $allDbPermissions = Permission::pluck('id')->toArray();
        \Log::debug('All permissions in DB', [
            'count' => count($allDbPermissions),
            'sample' => array_slice($allDbPermissions, 0, 5),
        ]);

        $existingPermissions = Permission::whereIn('id', $permissionIdsStr)->pluck('id')->toArray();
        \Log::debug('Found permissions matching request', [
            'requested' => $permissionIdsStr,
            'found' => $existingPermissions,
            'missing' => array_diff($permissionIdsStr, $existingPermissions),
        ]);

        $inserted = 0;
        $skipped = 0;

        foreach ($permissionIdsStr as $permId) {
            if (!in_array($permId, $existingPermissions)) {
                \Log::warning('Permission not found in DB, skipping', ['perm_id' => $permId]);
                $skipped++;
                continue;
            }

            // Verificar si ya existe en la tabla pivote
            $alreadyExists = DB::table('role_has_permissions')
                ->where('permission_id', $permId)
                ->where('role_id', $roleIdStr)
                ->exists();

            if ($alreadyExists) {
                \Log::debug('Permission already assigned, skipping', [
                    'perm_id' => $permId,
                    'role_id' => $roleIdStr,
                ]);
                $skipped++;
                continue;
            }

            // Insertar en la tabla pivote
            $result = DB::table('role_has_permissions')->insert([
                'permission_id' => $permId,
                'role_id' => $roleIdStr,
            ]);

            if ($result) {
                \Log::info('Permission assigned successfully', [
                    'perm_id' => $permId,
                    'role_id' => $roleIdStr,
                ]);
                $inserted++;
            } else {
                \Log::error('Failed to insert permission', [
                    'perm_id' => $permId,
                    'role_id' => $roleIdStr,
                ]);
            }
        }

        \Log::debug('=== ASSIGN PERMISSIONS END ===', [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'total_requested' => count($permissionIdsStr),
        ]);

        // Clear permission cache
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Revocar permisos de un rol.
     */
    public function revokePermissionsFromRole(string $roleId, array $permissionIds): void
    {
        $roleIdStr = (string) $roleId;
        $permissionIdsStr = array_map(fn($id) => (string) $id, $permissionIds);

        DB::table('role_has_permissions')
            ->where('role_id', $roleIdStr)
            ->whereIn('permission_id', $permissionIdsStr)
            ->delete();

        // Clear permission cache
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Crear un nuevo permiso.
     */
    public function createPermission(array $data): PermissionDTO
    {
        $permission = Permission::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
            'description' => $data['description'] ?? null,
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
     */
    public function deletePermission(string $permissionId): void
    {
        $permission = Permission::findOrFail($permissionId);
        $permission->delete();
    }

    /**
     * Obtener roles con sus permisos.
     */
    public function getRolesWithPermissions(): array
    {
        // Obtener roles directamente de la tabla para asegurar que existen
        $roles = DB::table('roles')
            ->select('id', 'name', 'prefix')
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        $rolesData = [];
        foreach ($roles as $role) {
            // Obtener permisos asignados a este rol desde la tabla pivote
            $permissionIds = DB::table('role_has_permissions')
                ->where('role_id', $role->id)
                ->pluck('permission_id')
                ->map(fn($id) => (string) $id)
                ->toArray();

            $rolesData[] = [
                'id' => (string) $role->id,
                'name' => $role->name,
                'prefix' => $role->prefix ?? $role->name,
                'permissions' => $permissionIds,
            ];
        }

        return $rolesData;
    }
}

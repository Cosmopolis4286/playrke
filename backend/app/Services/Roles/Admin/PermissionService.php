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

        return [
            'permissions' => $permissions->items(),
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
        // Los IDs se mantienen como strings para compatibilidad con Snowflake
        $roleIdStr = (string) $roleId;
        $permissionIdsStr = array_map(fn($id) => (string) $id, $permissionIds);

        // Verificar que el rol existe
        $roleExists = DB::table('roles')->where('id', $roleIdStr)->exists();
        if (!$roleExists) {
            throw new \Exception("Rol no encontrado con ID: {$roleId}");
        }

        // Verificar que los permisos existen
        $existingPermissions = Permission::whereIn('id', $permissionIdsStr)->pluck('id')->toArray();

        foreach ($permissionIdsStr as $permId) {
            if (!in_array($permId, $existingPermissions)) {
                continue;
            }

            // Insertar en la tabla pivote (ignora si ya existe)
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permId,
                'role_id' => $roleIdStr,
            ]);
        }

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

<?php

namespace App\Services\Roles\Admin;

use App\DTOs\Roles\Admin\Permission\PermissionDTO;
use App\DTOs\Roles\Admin\Permission\RolePermissionDTO;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
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
        $role = Role::findOrFail($roleId);
        $permissions = Permission::whereIn('id', $permissionIds)->get();
        
        $role->syncPermissions($permissions);
    }

    /**
     * Revocar permisos de un rol.
     */
    public function revokePermissionsFromRole(string $roleId, array $permissionIds): void
    {
        $role = Role::findOrFail($roleId);
        $permissions = Permission::whereIn('id', $permissionIds)->get();
        
        foreach ($permissions as $permission) {
            $role->revokePermissionTo($permission);
        }
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
        return Role::with('permissions')
            ->orderBy('name')
            ->get()
            ->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'prefix' => $role->prefix,
                'permissions' => $role->permissions->pluck('id')->toArray(),
            ])
            ->toArray();
    }
}

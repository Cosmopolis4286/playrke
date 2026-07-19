<?php

namespace App\Services\Roles\Admin;

use App\DTOs\Roles\Admin\Permission\PermissionDTO;
use App\DTOs\Roles\Admin\Permission\RolePermissionDTO;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
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
     */
    public function assignPermissionsToRole(string $roleId, array $permissionIds): void
    {
        $role = Role::findOrFail($roleId);

        $permissions = Permission::whereIn('id', $permissionIds)->get();

        if ($permissions->isNotEmpty()) {
            $role->givePermissionTo($permissions);
        }
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

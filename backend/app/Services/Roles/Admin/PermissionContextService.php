<?php

namespace App\Services\Roles\Admin;

use App\DTOs\Roles\Admin\Permission\PermissionContextDTO;
use App\Models\Permission;
use App\Models\Role;
use App\Traits\HashedTableDataTrait;

final class PermissionContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Services\Roles\Admin\PermissionService $permissionService,
    ) {}

    public function permissionContext(): PermissionContextDTO
    {
        // Obtener todos los permisos para el gestor de roles
        $permissions = Permission::with('roles')
            ->orderBy('name')
            ->get();

        \Log::debug('PermissionContext - Permissions loaded', [
            'count' => $permissions->count(),
            'sample_ids' => $permissions->take(3)->map(fn($p) => [
                'id' => $p->id,
                'id_type' => gettype($p->id),
                'name' => $p->name,
            ])->toArray(),
        ]);

        // Obtener todos los roles con sus permisos
        $rolesData = $this->getRolesWithPermissions();

        $mappedPermissions = $permissions->map(function ($permission) {
            return [
                'id' => (string) $permission->id, // Forzar string para evitar pérdida de precisión en JS
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'description' => $permission->description,
                'roles_badge' => $this->generateRolesBadge($permission->roles),
                'status_badge' => $this->statusBadge(true),
                'created_at' => $permission->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        \Log::debug('PermissionContext - Mapped permissions sample', [
            'first_permission' => $mappedPermissions[0] ?? null,
        ]);

        return new PermissionContextDTO(
            permissions: [
                'rows' => $mappedPermissions,
                'pagination' => null, // No hay paginación en el gestor de roles
            ],
            roles: $rolesData,
            pagination: [], // Vacío para compatibilidad
        );
    }

    private function getRolesWithPermissions(): array
    {
        // Obtener roles directamente de la tabla para asegurar consistencia
        $roles = \DB::table('roles')
            ->select('id', 'name', 'prefix')
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        $rolesData = [];
        foreach ($roles as $role) {
            // Obtener permisos asignados a este rol desde la tabla pivote
            $permissionIds = \DB::table('role_has_permissions')
                ->where('role_id', $role->id)
                ->pluck('permission_id')
                ->map(fn($id) => (string) $id)
                ->toArray();

            \Log::debug("Role {$role->name} permissions", [
                'role_id' => $role->id,
                'permission_ids' => $permissionIds,
            ]);

            $rolesData[] = [
                'id' => (string) $role->id,
                'name' => $role->name,
                'prefix' => $role->prefix ?? $role->name,
                'permissions' => $permissionIds,
            ];
        }

        return $rolesData;
    }

    private function generateRolesBadge($roles): string
    {
        if ($roles->isEmpty()) {
            return '<span class="badge badge-secondary">Sin roles</span>';
        }

        return $roles->map(function ($role) {
            $color = match($role->name) {
                'superadmin' => 'danger',
                'admin' => 'warning',
                'commerce' => 'info',
                'seller' => 'success',
                'treasurer' => 'primary',
                default => 'secondary'
            };
            return '<span class="badge badge-' . $color . '">' . $role->name . '</span>';
        })->implode(' ');
    }

    private function statusBadge(bool $status): string
    {
        $color = $status ? 'success' : 'danger';
        $text = $status ? 'Activo' : 'Inactivo';
        return '<span class="badge badge-' . $color . '">' . $text . '</span>';
    }
}

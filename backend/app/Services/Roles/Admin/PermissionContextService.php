<?php

namespace App\Services\Roles\Admin;

use App\DTOs\Roles\Admin\Permission\PermissionContextDTO;
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
        $permissions = \Spatie\Permission\Models\Permission::with('roles')
            ->orderBy('name')
            ->get();

        // Obtener todos los roles con sus permisos
        $rolesData = $this->getRolesWithPermissions();

        return new PermissionContextDTO(
            permissions: [
                'rows' => $permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'guard_name' => $permission->guard_name,
                        'description' => $permission->description,
                        'roles_badge' => $this->generateRolesBadge($permission->roles),
                        'status_badge' => $this->statusBadge(true),
                        'created_at' => $permission->created_at?->format('Y-m-d H:i:s'),
                    ];
                })->toArray(),
                'pagination' => null, // No hay paginación en el gestor de roles
            ],
            roles: $rolesData,
            pagination: [], // Vacío para compatibilidad
        );
    }

    private function getRolesWithPermissions(): array
    {
        return \Spatie\Permission\Models\Role::with('permissions')
            ->orderBy('name')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'prefix' => $role->prefix ?? $role->name, // Fallback si no hay prefix
                    'permissions' => $role->permissions->pluck('id')->toArray(),
                ];
            })
            ->toArray();
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

<?php

namespace App\Services\Roles\Admin;

use App\DTOs\Roles\Admin\Permission\PermissionContextDTO;
use App\Models\Permission;
use App\Traits\HashedTableDataTrait;

final class PermissionContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly PermissionService $permissionService,
    ) {}

    public function permissionContext(): PermissionContextDTO
    {
        $permissions = Permission::with('roles')
            ->orderBy('name')
            ->get();

        $mappedPermissions = $permissions->map(fn($permission) => [
            'id'          => (string) $permission->id,
            'name'        => $permission->name,
            'guard_name'  => $permission->guard_name,
            'description' => $permission->description,
            'is_system'   => (bool) $permission->is_system,
            'roles_badge' => $this->generateRolesBadge($permission->roles),
            'status_badge'=> $this->statusBadge(true),
            'created_at'  => $permission->created_at?->format('Y-m-d H:i:s'),
        ])->toArray();

        return new PermissionContextDTO(
            permissions: [
                'rows'       => $mappedPermissions,
                'pagination' => null,
            ],
            roles: $this->permissionService->getRolesWithPermissions(),
            pagination: [],
        );
    }

    private function generateRolesBadge($roles): string
    {
        if ($roles->isEmpty()) {
            return '<span class="badge badge-secondary">Sin roles</span>';
        }

        return $roles->map(function ($role) {
            $color = match ($role->name) {
                'superadmin' => 'danger',
                'admin'      => 'warning',
                'commerce'   => 'info',
                'seller'     => 'success',
                'treasurer'  => 'primary',
                default      => 'secondary',
            };
            return '<span class="badge badge-' . $color . '">' . e($role->name) . '</span>';
        })->implode(' ');
    }

    private function statusBadge(bool $status): string
    {
        $color = $status ? 'success' : 'danger';
        $text  = $status ? 'Activo' : 'Inactivo';
        return '<span class="badge badge-' . $color . '">' . $text . '</span>';
    }
}

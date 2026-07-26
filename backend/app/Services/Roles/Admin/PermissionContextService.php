<?php

namespace App\Services\Roles\Admin;

use App\DTOs\Roles\Admin\Permission\PermissionContextDTO;
use App\Models\Permission;

final class PermissionContextService
{
    public function __construct(
        private readonly PermissionService $permissionService,
    ) {}

    public function permissionContext(): PermissionContextDTO
    {
        $permissions = Permission::orderBy('name')->get();

        $mappedPermissions = $permissions->map(fn($permission) => [
            'id'          => (string) $permission->id,
            'name'        => $permission->name,
            'guard_name'  => $permission->guard_name,
            'description' => $permission->description,
            'is_system'   => (bool) $permission->is_system,
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
}

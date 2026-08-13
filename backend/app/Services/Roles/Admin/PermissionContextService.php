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
        // withCount('roles') evita cruzar colecciones en memoria para saber
        // qué permisos no están asignados a ningún rol ("huérfanos") — antes
        // no existía ninguna señal de esto en la matriz.
        $permissions = Permission::withCount('roles')->orderBy('name')->get();

        $mappedPermissions = $permissions->map(fn($permission) => [
            'id'          => (string) $permission->id,
            'name'        => $permission->name,
            'guard_name'  => $permission->guard_name,
            'description' => $permission->description,
            'is_system'   => (bool) $permission->is_system,
            'is_orphan'   => $permission->roles_count === 0,
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

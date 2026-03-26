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
        $data = $this->resolvePermissions();
        $roles = $this->getRolesData();

        return new PermissionContextDTO(
            permissions: $data['permissions'],
            roles: $roles,
            pagination: $data['pagination'],
        );
    }

    public function resolvePermissions(): array
    {
        $labels = ['Nombre', 'Guard', 'Descripción', 'Roles', 'Estado', 'Creado'];
        $rowKeys = ['name', 'guard_name', 'description', 'roles_badge', 'status_badge', 'created_at'];

        $source = $this->permissionService->getAllPermissions();
        $hashedData = $this->hashTableData($source, $labels, $rowKeys);

        return [
            'permissions' => [
                'rows' => $hashedData['rows'],
                'pagination' => $hashedData['pagination'],
            ],
            'pagination' => $hashedData['pagination'],
        ];
    }

    private function getRolesData(): array
    {
        return Role::orderBy('name')->get(['id', 'name', 'prefix'])->toArray();
    }
}

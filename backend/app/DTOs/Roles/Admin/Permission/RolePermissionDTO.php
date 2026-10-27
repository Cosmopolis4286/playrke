<?php

namespace App\DTOs\Roles\Admin\Permission;

final class RolePermissionDTO
{
    public function __construct(
        public string $roleId,
        public string $roleName,
        public string $rolePrefix,
        public array $permissions,
    ) {
    }
}

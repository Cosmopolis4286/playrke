<?php

namespace App\DTOs\Roles\Admin\Permission;

final class PermissionContextDTO
{
    public function __construct(
        public array $permissions,
        public array $roles,
        public array $pagination,
    ) {}
}

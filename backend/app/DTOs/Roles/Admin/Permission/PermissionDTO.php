<?php

namespace App\DTOs\Roles\Admin\Permission;

final class PermissionDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $guardName,
        public ?string $description = null,
        public bool $status = true,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }
}

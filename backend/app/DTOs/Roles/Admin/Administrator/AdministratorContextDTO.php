<?php

namespace App\DTOs\Roles\Admin\Administrator;

final class AdministratorContextDTO
{
    public function __construct(
        public array $administrators,
        public array $pagination,
        public array $countries,
        public array $roles,
    ) {}
}

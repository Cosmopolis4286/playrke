<?php

namespace App\DTOs\Roles\Admin\User;

use App\DTOs\ContextDTO;
use Illuminate\Support\Collection;

final class AdminUserContextDTO implements ContextDTO
{
    public function __construct(
        public readonly array $users,
        public readonly ?array $pagination,
        public readonly Collection $countries,
        public readonly Collection $roles,
    ) {
    }

    public function toView() : array
    {
        return [
            'users' => $this->users,
            'users_pagination' => $this->pagination,
            'countries' => $this->countries,
            'roles' => $this->roles,
        ];
    }
}

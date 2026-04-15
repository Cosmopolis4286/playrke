<?php

namespace App\Policies\Games;

use App\Policies\Roles\BaseRolePolicy;

class GamePolicy extends BaseRolePolicy
{
    protected function requiredRole(): string|array
    {
        return ['superadmin', 'admin'];
    }
}

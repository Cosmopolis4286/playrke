<?php

namespace App\Policies;

use App\Policies\Roles\BaseRolePolicy;

class SettingPolicy extends BaseRolePolicy
{
    protected function requiredRole(): string|array
    {
        return ['superadmin', 'admin'];
    }
}

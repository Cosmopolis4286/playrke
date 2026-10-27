<?php

namespace App\Policies;

use App\Policies\Roles\BaseRolePolicy;

class AppParameterPolicy extends BaseRolePolicy
{
    protected function requiredRole() : string|array
    {
        return ['superadmin', 'admin'];
    }
}

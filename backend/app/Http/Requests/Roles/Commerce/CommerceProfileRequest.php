<?php

namespace App\Http\Requests\Roles\Commerce;

use App\Http\Requests\Roles\RoleProfileRequest;
use App\Models\Roles\Commerce;

/**
 * Autogestión de la ficha propia de Commerce — reglas en RoleProfileRequest.
 */
class CommerceProfileRequest extends RoleProfileRequest
{
    protected function entityClass(): string
    {
        return Commerce::class;
    }

    protected function ownerPrefix(): string
    {
        return 'commerce';
    }
}

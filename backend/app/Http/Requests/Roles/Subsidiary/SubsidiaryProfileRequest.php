<?php

namespace App\Http\Requests\Roles\Subsidiary;

use App\Http\Requests\Roles\RoleProfileRequest;
use App\Models\Roles\Subsidiary;

/**
 * Autogestión de la ficha propia de Subsidiary — reglas en RoleProfileRequest.
 */
class SubsidiaryProfileRequest extends RoleProfileRequest
{
    protected function entityClass(): string
    {
        return Subsidiary::class;
    }

    protected function ownerPrefix(): string
    {
        return 'subsidiary';
    }
}

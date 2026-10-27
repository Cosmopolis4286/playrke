<?php

namespace App\Http\Requests\Roles\Treasurer;

use App\Http\Requests\Roles\RoleProfileRequest;
use App\Models\Roles\Treasurer;

/**
 * Autogestión de la ficha propia de Treasurer — reglas en RoleProfileRequest.
 */
class TreasurerProfileRequest extends RoleProfileRequest
{
    protected function entityClass() : string
    {
        return Treasurer::class;
    }

    protected function ownerPrefix() : string
    {
        return 'treasurer';
    }
}

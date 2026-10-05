<?php

namespace App\Http\Requests\Roles\Customer;

use App\Http\Requests\Roles\RoleProfileRequest;
use App\Models\Roles\Customer;

/**
 * Autogestión de la ficha propia de Customer — reglas en RoleProfileRequest.
 */
class CustomerProfileRequest extends RoleProfileRequest
{
    protected function entityClass(): string
    {
        return Customer::class;
    }

    protected function ownerPrefix(): string
    {
        return 'customer';
    }
}

<?php

namespace App\Http\Requests\Roles\Seller;

use App\Http\Requests\Roles\RoleProfileRequest;
use App\Models\Roles\Seller;

/**
 * Autogestión de la ficha propia de Seller — reglas en RoleProfileRequest.
 */
class SellerProfileRequest extends RoleProfileRequest
{
    protected function entityClass() : string
    {
        return Seller::class;
    }

    protected function ownerPrefix() : string
    {
        return 'seller';
    }
}

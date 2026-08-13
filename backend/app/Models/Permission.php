<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\SnowflakeId;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Extiende el modelo de Spatie agregando SnowflakeId + is_system.
 * El trait aporta: keyType=string, incrementing=false, getCasts() con id=>string,
 * y generación automática del ID en el evento creating.
 */
class Permission extends SpatiePermission
{
    use SnowflakeId, Auditable;

    protected $casts = [
        'is_system' => 'boolean',
    ];
}

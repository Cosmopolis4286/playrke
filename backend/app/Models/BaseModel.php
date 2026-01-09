<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use App\Traits\SnowflakeId;
use App\Models\Concerns\HasHashedRouteKey;

abstract class BaseModel extends Model
{
    use SoftDeletes;
    use Auditable;
    use SnowflakeId;
    use HasHashedRouteKey;

    /**
     * Evita asignación masiva accidental
     */
    protected $guarded = [];
}

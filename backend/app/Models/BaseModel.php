<?php

namespace App\Models;

use App\Models\Concerns\HasHashedRouteKey;
use App\Traits\Auditable;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class BaseModel extends Model
{
    use Auditable;
    use HasFactory;
    use HasHashedRouteKey;
    use SnowflakeId;
    use SoftDeletes;

    /**
     * Evita asignación masiva accidental
     */
    protected $guarded = [];
}

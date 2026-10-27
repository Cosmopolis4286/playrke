<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use Auditable, SnowflakeId;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $fillable = [
        'key',
        'label',
        'is_active',
    ];
}

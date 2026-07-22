<?php

namespace App\Models;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'key',
        'label',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

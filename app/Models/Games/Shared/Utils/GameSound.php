<?php

namespace App\Models\Games\Shared\Utils;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

class GameSound extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'file',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

<?php

namespace App\Models\Games\Common\Utils;

use App\Casts\AsActiveStatusEnum;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

class GameSound extends Model
{
    use SnowflakeId;

    protected $casts = [
        'status' => AsActiveStatusEnum::class,
    ];

    protected $fillable = [
        'file',
        'description',
        'status',
    ];
}

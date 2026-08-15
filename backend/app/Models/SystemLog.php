<?php

namespace App\Models;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    use SnowflakeId;

    protected $table = 'system_logs';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'level',
        'message',
        'context',
        'stack',
        'meta',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];
}

<?php

namespace App\Models;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'fingerprint',
        'occurrences',
        'last_seen_at',
        'reviewed_at',
        'reviewed_by_user_id',
    ];

    protected $casts = [
        'meta'         => 'array',
        'created_at'   => 'datetime',
        'last_seen_at' => 'datetime',
        'reviewed_at'  => 'datetime',
        'occurrences'  => 'integer',
    ];

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}

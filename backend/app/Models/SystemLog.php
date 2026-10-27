<?php

namespace App\Models;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    use SnowflakeId;
    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'ignored_at' => 'datetime',
        'alert_sent_at' => 'datetime',
        'occurrences' => 'integer',
    ];

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
        'ignored_at',
        'ignored_by_user_id',
        'assigned_to_user_id',
        'alert_sent_at',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'system_logs';

    public function assignedTo() : BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function ignoredBy() : BelongsTo
    {
        return $this->belongsTo(User::class, 'ignored_by_user_id');
    }

    public function reviewedBy() : BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}

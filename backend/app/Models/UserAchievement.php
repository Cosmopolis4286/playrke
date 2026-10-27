<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    use Auditable;
    use SnowflakeId;

    public $incrementing = false;

    protected $casts = [
        'unlocked_at' => 'datetime',
        'progress_data' => 'array',
        'is_seen' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'achievement_id',
        'unlocked_at',
        'progress_data',
        'is_seen',
    ];

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $table = 'user_achievements';

    public function achievement() : BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    public function markAsSeen() : bool
    {
        return $this->update(['is_seen' => true]);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecentlyUnlocked($query, int $hours = 24)
    {
        return $query->where('unlocked_at', '>=', now()->subHours($hours));
    }

    public function scopeUnseen($query)
    {
        return $query->where('is_seen', false);
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UserAchievement extends Model
{
    use SnowflakeId;
    use Auditable;

    protected $table = 'user_achievements';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'achievement_id',
        'unlocked_at',
        'progress_data',
        'is_seen',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'progress_data' => 'array',
        'is_seen' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    public function markAsSeen(): bool
    {
        return $this->update(['is_seen' => true]);
    }

    public function scopeUnseen($query)
    {
        return $query->where('is_seen', false);
    }

    public function scopeRecentlyUnlocked($query, int $hours = 24)
    {
        return $query->where('unlocked_at', '>=', now()->subHours($hours));
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}

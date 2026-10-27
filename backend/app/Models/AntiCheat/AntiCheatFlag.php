<?php

namespace App\Models\AntiCheat;

use App\Enums\Games\Common\AntiCheatSignalType;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AntiCheatFlag extends Model
{
    use SnowflakeId;

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'signal_type' => AntiCheatSignalType::class,
        'severity' => 'integer',
        'metadata' => 'array',
        'triggered_suspension' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id', 'game_slug', 'game_id', 'signal_type',
        'severity', 'metadata', 'triggered_suspension',
    ];

    protected $table = 'anti_cheat_flags';

    public function scopeForGame($query, string $gameSlug)
    {
        return $query->where('game_slug', $gameSlug);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, AntiCheatSignalType $type)
    {
        return $query->where('signal_type', $type);
    }

    public function scopeRecent($query, int $windowMinutes)
    {
        return $query->where('created_at', '>=', now()->subMinutes($windowMinutes));
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

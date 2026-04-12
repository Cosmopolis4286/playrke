<?php

namespace App\Models\Games\Trivia;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TriviaGame extends BaseModel
{
    protected $connection = 'pgsql_trivia';
    protected $table = 'trivia_games';

    protected $fillable = [
        'room_id',
        'game_code',
        'status',
        'current_question_index',
        'total_questions',
        'player_count',
        'time_per_question',
        'winner_user_id',
        'started_at',
        'ended_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    // Relaciones
    public function room(): BelongsTo
    {
        return $this->belongsTo(TriviaRoom::class, 'room_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(TriviaScore::class, 'game_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['waiting', 'playing', 'paused']);
    }

    public function scopeFinished($query)
    {
        return $query->whereIn('status', ['finished', 'cancelled']);
    }

    // Helpers
    public function isActive(): bool
    {
        return in_array($this->status, ['waiting', 'playing', 'paused']);
    }

    public function isPlaying(): bool
    {
        return $this->status === 'playing';
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['finished', 'cancelled']);
    }
}

<?php

namespace App\Models\Games\Trivia;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriviaParticipant extends BaseModel
{
    protected $connection = 'pgsql_trivia';
    protected $table = 'trivia_participants';

    protected $fillable = [
        'room_id',
        'team_id',              // null si es free_for_all
        'user_id',
        'status',               // registered, confirmed, playing, eliminated, winner, abandoned
        'registered_at',
        'confirmed_at',
        'paid_amount',
        'paid_currency_id',
        'paid_amount_usd',
        'joined_game_at',
        'eliminated_at',
        'final_rank',
        'prize_won',
        'power_ups',
        'power_ups_used',
        'correct_answers',
        'wrong_answers',
        'total_score',
        'total_time',
        'metadata',
    ];

    protected $casts = [
        'paid_amount' => 'decimal:8',
        'paid_amount_usd' => 'decimal:8',
        'prize_won' => 'decimal:8',
        'power_ups' => 'array',
        'power_ups_used' => 'array',
        'total_time' => 'decimal:3',
        'metadata' => 'array',
        'registered_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'joined_game_at' => 'datetime',
        'eliminated_at' => 'datetime',
    ];

    // Relaciones
    public function room(): BelongsTo
    {
        return $this->belongsTo(TriviaRoom::class, 'room_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Games\Trivia\Models\TriviaTeam::class, 'team_id');
    }

    // El usuario está en MySQL, no se puede hacer relación directa Eloquent
    // Se accede mediante user_id manualmente

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['confirmed', 'playing']);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeFinished($query)
    {
        return $query->whereIn('status', ['eliminated', 'winner', 'abandoned']);
    }

    // Helpers
    public function isConfirmed(): bool
    {
        return in_array($this->status, ['confirmed', 'playing', 'winner']);
    }

    public function isPlaying(): bool
    {
        return $this->status === 'playing';
    }

    public function isEliminated(): bool
    {
        return $this->status === 'eliminated';
    }

    public function isWinner(): bool
    {
        return $this->status === 'winner';
    }

    // Comodines disponibles
    public function getAvailablePowerUp(string $type): int
    {
        $powerUps = $this->power_ups ?? [];
        $used = $this->power_ups_used ?? [];

        $total = $powerUps[$type] ?? 0;
        $usedCount = $used[$type] ?? 0;

        return max(0, $total - $usedCount);
    }

    public function usePowerUp(string $type): bool
    {
        if ($this->getAvailablePowerUp($type) <= 0) {
            return false;
        }

        $used = $this->power_ups_used ?? [];
        $used[$type] = ($used[$type] ?? 0) + 1;
        $this->power_ups_used = $used;

        return true;
    }
}

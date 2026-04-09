<?php

declare(strict_types=1);

namespace App\Domains\Trivia\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo: Juego de Trivia (trivia_games).
 * 
 * Representa una instancia específica de juego.
 * Datos históricos y persistencia final.
 */
class TriviaGame extends BaseModel
{
    protected $table = 'trivia_games';

    protected $fillable = [
        'room_id',
        'game_code',
        'status', // 'active', 'paused', 'finished', 'cancelled'
        'current_question_index',
        'total_questions',
        'player_count',
        'started_at',
        'ended_at',
        'metadata', // JSON
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'current_question_index' => 'integer',
        'total_questions' => 'integer',
        'player_count' => 'integer',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(TriviaRoom::class, 'room_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TriviaAnswer::class, 'game_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(TriviaScore::class, 'game_id');
    }

    /**
     * Scope: Juegos activos.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Finalizar juego y guardar resultados.
     */
    public function finish(array $finalScores = []): void
    {
        $this->update([
            'status' => 'finished',
            'ended_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], [
                'final_scores' => $finalScores,
            ]),
        ]);
    }
}

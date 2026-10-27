<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo: Puntuación Final (trivia_scores).
 *
 * Guarda resultados finales del juego.
 */
class TriviaScore extends BaseModel
{
    protected $casts = [
        'final_score' => 'integer',
        'correct_answers' => 'integer',
        'wrong_answers' => 'integer',
        'average_response_time_ms' => 'float',
        'position' => 'integer',
        'max_streak' => 'integer',
        'is_winner' => 'boolean',
        'total_questions' => 'integer',
        'total_response_time_ms' => 'integer',
        'metadata' => 'array',
    ];
    protected $connection = 'pgsql_trivia';

    protected $fillable = [
        'game_id',
        'player_id', // Referencia externa, no FK
        'final_score',
        'correct_answers',
        'wrong_answers',
        'average_response_time_ms',
        'position',
        'max_streak',
        'is_winner',
        'total_questions',
        'total_response_time_ms',
        'metadata', // JSON con historial detallado
    ];

    protected $table = 'trivia_scores';

    public function game() : BelongsTo
    {
        return $this->belongsTo(TriviaGame::class, 'game_id');
    }

    /**
     * Scope: Top scores de un juego.
     */
    public function scopeTop($query, int $limit = 10)
    {
        return $query->orderBy('position')->limit($limit);
    }
}

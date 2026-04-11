<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo: Respuesta de Jugador (trivia_answers).
 * 
 * Persistencia histórica de todas las respuestas.
 */
class TriviaAnswer extends BaseModel
{
    protected $connection = 'pgsql_trivia';

    protected $table = 'trivia_answers';

    protected $fillable = [
        'game_id',
        'player_id', // Referencia externa, no FK
        'question_id',
        'submitted_answer',
        'is_correct',
        'points_earned',
        'response_time_ms',
        'answered_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'points_earned' => 'integer',
        'response_time_ms' => 'float',
        'answered_at' => 'datetime',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(TriviaGame::class, 'game_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TriviaQuestion::class, 'question_id');
    }
}

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
        'selected_option_index',
        'is_correct',
        'points_earned',
        'response_time_ms',
        'is_late',
        'client_id',
    ];

    protected $casts = [
        'selected_option_index' => 'integer',
        'is_correct' => 'boolean',
        'points_earned' => 'integer',
        'response_time_ms' => 'float',
        'is_late' => 'boolean',
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

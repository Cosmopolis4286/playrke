<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Models;

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
    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'current_question_index' => 'integer',
        'total_questions' => 'integer',
        'time_per_question' => 'integer',
        'player_count' => 'integer',
    ];
    protected $connection = 'pgsql_trivia';

    protected $fillable = [
        'room_id',
        'game_code',
        'status', // 'playing', 'paused', 'finished', 'cancelled'
        'current_question_index',
        'total_questions',
        'time_per_question',
        'player_count',
        'started_at',
        'ended_at',
        'metadata', // JSON
    ];

    protected $table = 'trivia_games';

    public function answers() : HasMany
    {
        return $this->hasMany(TriviaAnswer::class, 'game_id');
    }

    /**
     * Finalizar juego y guardar resultados.
     */
    public function finish(array $finalScores = []) : void
    {
        $this->update([
            'status' => 'finished',
            'ended_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], [
                'final_scores' => $finalScores,
            ]),
        ]);
    }

    public function room() : BelongsTo
    {
        return $this->belongsTo(TriviaRoom::class, 'room_id');
    }

    /**
     * Scope: Juegos activos.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['playing', 'active']);
    }

    public function scores() : HasMany
    {
        return $this->hasMany(TriviaScore::class, 'game_id');
    }
}

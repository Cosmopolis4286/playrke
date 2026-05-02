<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo: Equipo de Trivia (trivia_teams).
 *
 * Representa un equipo en salas con modo teams o duel.
 */
class TriviaTeam extends BaseModel
{
    protected $connection = 'pgsql_trivia';

    protected $table = 'trivia_teams';

    protected $fillable = [
        'room_id',
        'name',
        'color',
        'icon',
        'max_players',
        'current_players',
        'team_number',
        'metadata',
    ];

    protected $casts = [
        'max_players'     => 'integer',
        'current_players' => 'integer',
        'team_number'     => 'integer',
        'metadata'        => 'array',
    ];

    /**
     * Relación: Sala a la que pertenece el equipo.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(TriviaRoom::class, 'room_id');
    }

    /**
     * Relación: Participantes en este equipo.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(TriviaParticipant::class, 'team_id');
    }

    /**
     * Calcular puntuación total del equipo.
     */
    public function calculateTotalScore(): int
    {
        return $this->participants()
            ->whereNotNull('total_score')
            ->sum('total_score') ?? 0;
    }

    /**
     * Contar respuestas correctas del equipo.
     */
    public function countCorrectAnswers(): int
    {
        return $this->participants()
            ->whereNotNull('correct_answers')
            ->sum('correct_answers') ?? 0;
    }

    /**
     * Verificar si el equipo tiene cupo disponible.
     */
    public function hasSpace(): bool
    {
        return $this->current_players < $this->max_players;
    }

    /**
     * Scope: Equipos ordenados por puntuación.
     */
    public function scopeByScore($query)
    {
        return $query->withSum('participants as total_score', 'total_score')
            ->orderByDesc('total_score');
    }
}

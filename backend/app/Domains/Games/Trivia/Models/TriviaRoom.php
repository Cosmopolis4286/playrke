<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo: Sala de Trivia (trivia_rooms).
 *
 * Representa una sala/sesión de juego en la base de datos.
 * El estado en tiempo real se maneja en Redis, no aquí.
 */
class TriviaRoom extends BaseModel
{
    protected $connection = 'pgsql_trivia';

    protected $table = 'trivia_rooms';

    protected $fillable = [
        'code',
        'title',
        'description',
        'category',
        'host_id',
        'status', // 'waiting', 'playing', 'closed', 'cancelled'
        'game_mode', // 'free_for_all', 'teams', 'duel', 'tournament'
        'teams_config',       // jsonb
        'max_players',
        'question_count',
        'time_per_question',
        'scheduled_at',
        'started_at',
        'ended_at',
        'settings',           // jsonb (ex config)
        'metadata',           // jsonb
        'current_players',
        'entry_fee',
        'entry_currency_type',
        'entry_currency_id',
        'local_currency_id',
        'entry_fee_usd',
        'prize_type',
        'prize_amount',
        'prize_currency_id',
        'commission_percent',
    ];

    protected $casts = [
        'settings'           => 'array',
        'metadata'           => 'array',
        'teams_config'       => 'array',
        'scheduled_at'       => 'datetime',
        'started_at'         => 'datetime',
        'ended_at'           => 'datetime',
        'max_players'        => 'integer',
        'question_count'     => 'integer',
        'time_per_question'  => 'integer',
        'current_players'    => 'integer',
    ];

    /**
     * Relación: Juegos en esta sala.
     */
    public function games(): HasMany
    {
        return $this->hasMany(TriviaGame::class, 'room_id');
    }

    /**
     * Relación: Equipos de esta sala (para modos teams/duel).
     */
    public function teams(): HasMany
    {
        return $this->hasMany(TriviaTeam::class, 'room_id');
    }

    /**
     * Relación: Preguntas de esta sala.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(TriviaQuestion::class, 'room_id');
    }

    /**
     * Verificar si la sala usa equipos.
     */
    public function usesTeams(): bool
    {
        return in_array($this->game_mode, ['teams', 'duel']);
    }

    /**
     * Verificar si es modo duel (2 equipos o 1vsX).
     */
    public function isDuelMode(): bool
    {
        return $this->game_mode === 'duel';
    }

    /**
     * Scope: Salas disponibles para unirse.
     */
    public function scopeJoinable($query)
    {
        return $query->where('status', 'waiting')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', now()->addMinutes(5));
            });
    }

    /**
     * Verificar si la sala puede iniciarse.
     */
    public function canStart(): bool
    {
        return $this->status === 'waiting' &&
               $this->games()->where('status', 'active')->doesntExist();
    }

    /**
     * Verificar si está activa.
     */
    public function isActive(): bool
    {
        return $this->status === 'playing';
    }
}

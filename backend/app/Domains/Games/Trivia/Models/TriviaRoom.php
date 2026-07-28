<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Models;

use App\Models\BaseModel;
use App\Models\Games\Common\GameMode\GameMode;
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
        'game_mode', // legacy — se mantiene en BD, ya no es la fuente de verdad (ver game_mode_id)
        'game_mode_id', // referencia blanda a game_modes.id (MySQL) — sin FK física, cross-database
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
        'fee_by_currency',
        'local_currency_id',
        'entry_fee_usd',
        'prize_type',
        'prize_amount',
        'prize_currency_id',
        'commission_percent',
        'prize_model',
        'prize_payouts',
        'platform_rake_percent',
        'sponsor_prize',
        'pool_accumulated',
    ];

    protected $casts = [
        'settings'           => 'array',
        'metadata'           => 'array',
        'teams_config'       => 'array',
        'fee_by_currency'    => 'array',
        'prize_payouts'      => 'array',
        'scheduled_at'       => 'datetime',
        'started_at'         => 'datetime',
        'ended_at'           => 'datetime',
        'max_players'        => 'integer',
        'question_count'     => 'integer',
        'time_per_question'  => 'integer',
        'current_players'    => 'integer',
        'game_mode_id'       => 'integer',
        'platform_rake_percent' => 'decimal:2',
        'sponsor_prize'         => 'decimal:8',
        'pool_accumulated'      => 'decimal:8',
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
     * Relación blanda al catálogo de modos de juego (MySQL, conexión
     * 'default'). Sin FK física: trivia_rooms vive en Postgres
     * (pgsql_trivia), por lo que Eloquent resuelve esto con una query
     * separada contra la otra conexión, no un JOIN real.
     */
    public function gameMode(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GameMode::class, 'game_mode_id');
    }

    /**
     * Verificar si la sala usa equipos.
     */
    public function usesTeams(): bool
    {
        return in_array($this->gameModeCode(), ['TEAMS', 'DUEL']);
    }

    /**
     * Verificar si es modo duel (2 equipos o 1vsX).
     */
    public function isDuelMode(): bool
    {
        return $this->gameModeCode() === 'DUEL';
    }

    /**
     * Código del modo de juego actual, resuelto vía game_mode_id.
     * Cae al string legacy `game_mode` (uppercased) si el modo no está
     * asignado — cubre datos previos al backfill o filas creadas por código
     * que todavía no haya migrado a enviar game_mode_id.
     */
    public function gameModeCode(): ?string
    {
        return $this->gameMode?->code ?? ($this->game_mode ? strtoupper($this->game_mode) : null);
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

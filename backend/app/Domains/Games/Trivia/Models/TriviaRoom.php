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

    /**
     * game_mode ya no es columna de BD (ver gameModeCode()/getGameModeAttribute
     * más abajo) — se mantiene como atributo serializado para no romper el
     * contrato que el frontend (Lobby.vue, RoomsIndex.vue) ya espera.
     */
    protected $appends = ['game_mode'];

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
        // setConnection('mysql') explícito sobre la instancia relacionada, y
        // BelongsTo construido a mano (no vía $this->belongsTo(), que ignora
        // la conexión ya fijada en el Builder pasado): sin esto la relación
        // hereda la conexión pgsql_trivia de TriviaRoom (confirmado con
        // tinker) y la query termina corriendo contra Postgres, donde
        // game_modes no existe ("relation game_modes does not exist") —
        // GameMode no fija su propia conexión (usa el default), lo cual
        // normalmente basta, pero no evita la herencia en esta relación
        // cross-database.
        $relatedGameMode = (new GameMode())->setConnection('mysql');

        return new \Illuminate\Database\Eloquent\Relations\BelongsTo(
            $relatedGameMode->newQuery(),
            $this,
            'game_mode_id',
            'id',
            'gameMode'
        );
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
     *
     * Llama gameMode() (el método de relación) explícitamente, no $this->gameMode
     * — el accessor getGameModeAttribute() de abajo también resuelve al nombre
     * mágico "gameMode" en Eloquent, y acceder vía propiedad aquí crea un
     * conflicto de resolución entre accessor y relación (ErrorException
     * "Undefined property").
     */
    public function gameModeCode(): ?string
    {
        return $this->gameMode()->first()?->code;
    }

    /**
     * Accessor: expone game_mode como código legible en minúsculas
     * ('free_for_all', 'teams', 'duel', ...) para el frontend — ver
     * comentario en $appends.
     */
    public function getGameModeAttribute(): ?string
    {
        $code = $this->gameModeCode();

        return $code === null ? null : strtolower($code);
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
     *
     * Usa TriviaGame::scopeActive() (status IN ['playing','active']) en vez
     * de comparar contra el string 'active' directamente: GameEngineService
     * crea las partidas con status 'playing', así que la comparación
     * exacta contra 'active' nunca coincidía y esta guarda jamás bloqueaba
     * un segundo inicio sobre una sala con partida ya en curso.
     */
    public function canStart(): bool
    {
        return $this->status === 'waiting' &&
               $this->games()->active()->doesntExist();
    }

    /**
     * Verificar si está activa.
     */
    public function isActive(): bool
    {
        return $this->status === 'playing';
    }
}

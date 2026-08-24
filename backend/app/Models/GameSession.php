<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sesión de partida mínima — ver progreso/game-engine-plan.md.
 *
 * Deliberadamente NO es una máquina de estados genérica para Bingo/Trivia
 * (cada uno resuelve su propio ciclo de vida de forma madura). Su propósito
 * hoy es dar un ancla real a WalletHold.game_session_id para juegos futuros
 * de duración variable. Sin lógica de negocio en el modelo — las
 * transiciones de estado viven en GameSessionService.
 *
 * @property string $id
 * @property string $game
 * @property string $status WAITING | RUNNING | FINISHED | CANCELLED
 * @property string|null $initiator_user_id
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $finished_at
 * @property array|null $metadata
 */
class GameSession extends Model
{
    use \App\Traits\SnowflakeId;

    protected $table = 'game_sessions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_WAITING   = 'WAITING';
    public const STATUS_RUNNING   = 'RUNNING';
    public const STATUS_FINISHED  = 'FINISHED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'game',
        'status',
        'initiator_user_id',
        'started_at',
        'finished_at',
        'metadata',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_user_id');
    }

    public function holds(): HasMany
    {
        return $this->hasMany(WalletHold::class, 'game_session_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_WAITING, self::STATUS_RUNNING], true);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_WAITING, self::STATUS_RUNNING]);
    }

    public function scopeForGame($query, string $game)
    {
        return $query->where('game', $game);
    }
}

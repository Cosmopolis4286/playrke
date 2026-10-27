<?php

namespace App\Models;

use App\Domains\Wallet\Models\WalletHold;
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
 * @property string              $id
 * @property string              $game
 * @property string              $status            WAITING | RUNNING | FINISHED | CANCELLED
 * @property float|null          $entry_fee
 * @property string|null         $currency_id
 * @property float|null          $prize_pool
 * @property int|null            $max_participants
 * @property int|null            $min_level
 * @property string|null         $initiator_user_id
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $finished_at
 * @property array|null          $metadata
 */
class GameSession extends Model
{
    use \App\Traits\SnowflakeId;
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_FINISHED = 'FINISHED';
    public const STATUS_RUNNING = 'RUNNING';

    public const STATUS_WAITING = 'WAITING';
    public $incrementing = false;

    protected $casts = [
        'entry_fee' => 'float',
        'prize_pool' => 'float',
        'max_participants' => 'integer',
        'min_level' => 'integer',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'game',
        'status',
        'entry_fee',
        'currency_id',
        'prize_pool',
        'max_participants',
        'min_level',
        'initiator_user_id',
        'started_at',
        'finished_at',
        'metadata',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'game_sessions';

    public function activeParticipants() : HasMany
    {
        return $this->participants()->active();
    }

    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * ¿Tiene cupo para un participante más? true si max_participants es
     * null (sin límite) o si el conteo activo todavía no lo alcanza.
     */
    public function hasAvailableSlot() : bool
    {
        if ($this->max_participants === null) {
            return true;
        }

        return $this->activeParticipants()->count() < $this->max_participants;
    }

    public function holds() : HasMany
    {
        return $this->hasMany(WalletHold::class, 'game_session_id');
    }

    public function initiator() : BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_user_id');
    }

    public function isActive() : bool
    {
        return in_array($this->status, [self::STATUS_WAITING, self::STATUS_RUNNING], true);
    }

    public function participants() : HasMany
    {
        return $this->hasMany(GameSessionParticipant::class, 'game_session_id');
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

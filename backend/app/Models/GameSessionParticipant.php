<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Participación EN CURSO de un jugador en una GameSession — distinta de
 * GameParticipation (resultado final, ya usada por Bingo/Trivia vía
 * GameCompletionService). Ver migración de creación para el porqué de la
 * distinción.
 *
 * @property string $id
 * @property string $game_session_id
 * @property string $user_id
 * @property string $status JOINED | LEFT
 * @property \Carbon\Carbon $joined_at
 * @property \Carbon\Carbon|null $left_at
 */
class GameSessionParticipant extends Model
{
    use \App\Traits\SnowflakeId;

    protected $table = 'game_session_participants';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_JOINED = 'JOINED';
    public const STATUS_LEFT   = 'LEFT';

    protected $fillable = [
        'game_session_id',
        'user_id',
        'status',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'joined_at'  => 'datetime',
        'left_at'    => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_JOINED;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_JOINED);
    }
}

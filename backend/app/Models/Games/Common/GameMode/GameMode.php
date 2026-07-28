<?php

namespace App\Models\Games\Common\GameMode;

use App\Models\Game;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modalidad de participación de un juego (Solitario, Múltiple, Ranking,
 * etc.), administrable por admins. Cada modo pertenece a un único juego
 * (game_id) — no se comparten modos entre juegos.
 */
class GameMode extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'game_id',
        'code',
        'label',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'game_id' => 'string',
        'code' => 'string',
        'label' => 'string',
        'status' => 'string',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }
}

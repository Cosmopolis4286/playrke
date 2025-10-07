<?php

namespace App\Models\Users\Roles\Commerce;

use App\Models\Games\Shared\Game;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para permisos de juegos de comercios
 */
class CommerceGame extends Model
{
    use SnowflakeId;

    protected $table = 'commerces_games';

    protected $fillable = [
        'commerce_id',
        'game_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Relación con el comercio
     */
    public function commerce(): BelongsTo
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
    }

    /**
     * Relación con el juego
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}
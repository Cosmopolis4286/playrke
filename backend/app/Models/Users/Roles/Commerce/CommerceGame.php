<?php

namespace App\Models\Users\Commerce;

use App\Models\Games\Game;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para permisos de juegos de comercios
 */
class CommerceGame extends Pivot
{
    public $incrementing = false;
    protected $primaryKey = null;
    protected $keyType = 'string';

    protected $table = 'commerces_games';

    protected $fillable = [
        'commerce_id',
        'game_id',
        'status'
    ];

    protected $casts = [
        'commerce_id' => 'string',
        'game_id' => 'string',
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

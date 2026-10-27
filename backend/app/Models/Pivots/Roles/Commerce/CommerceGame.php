<?php

namespace App\Models\Pivots\Roles\Commerce;

use App\Models\Game;
use App\Models\Roles\Commerce;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Modelo para permisos de juegos de los comercios
 */
class CommerceGame extends Pivot
{
    public $incrementing = false;

    protected $casts = [
        'commerce_id' => 'string',
        'game_id' => 'string',
    ];

    protected $fillable = [
        'commerce_id',
        'game_id',
        'status',
    ];
    protected $keyType = 'string';
    protected $primaryKey = null;

    protected $table = 'commerces_games';

    /**
     * Relación con el comercio
     */
    public function commerce() : BelongsTo
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
    }

    /**
     * Relación con el juego
     */
    public function game() : BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}

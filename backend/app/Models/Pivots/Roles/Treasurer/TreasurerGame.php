<?php

namespace App\Models\Pivots\Roles\Treasurer;

use App\Models\Game;
use App\Models\Roles\Treasurer;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para permisos de juegos de los tesoreros
 */
class TreasurerGame extends Pivot
{
    public $incrementing = false;
    protected $primaryKey = null;
    protected $keyType = 'string';

    protected $table = 'treasurers_games';

    protected $fillable = [
        'treasurer_id',
        'game_id',
        'status'
    ];

    protected $casts = [
        'treasurer_id' => 'string',
        'game_id' => 'string',
    ];

    /**
     * Relación con el comercio
     */
    public function treasurer(): BelongsTo
    {
        return $this->belongsTo(Treasurer::class, 'treasurer_id');
    }

    /**
     * Relación con el juego
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}

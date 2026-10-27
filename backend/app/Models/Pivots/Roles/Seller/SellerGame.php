<?php

namespace App\Models\Pivots\Roles\Seller;

use App\Models\Game;
use App\Models\Pivots\BasePivot;
use App\Models\Roles\Seller;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para permisos de juegos de los vendedores
 */
class SellerGame extends BasePivot
{
    public $incrementing = false;

    protected $casts = [
        'seller_id' => 'string',
        'game_id' => 'string',
    ];

    protected $fillable = [
        'seller_id',
        'game_id',
        'status',
    ];
    protected $keyType = 'string';
    protected $primaryKey = null;

    protected $table = 'sellers_games';

    /**
     * Relación con el juego
     */
    public function game() : BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * Relación con el comercio
     */
    public function seller() : BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
}

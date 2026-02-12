<?php

namespace App\Models\Pivots\Roles\Customer;

use App\Models\Game;
use App\Models\Roles\Customer;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para permisos de juegos de clientes
 */
class CustomerGame extends Pivot
{
    public $incrementing = false;
    protected $primaryKey = null;
    protected $keyType = 'string';

    protected $table = 'customers_games';

    protected $fillable = [
        'customer_id',
        'game_id',
        'status'
    ];

    protected $casts = [
        'customer_id' => 'string',
        'game_id' => 'string',
    ];

    /**
     * Relación con el comercio
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Relación con el juego
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}

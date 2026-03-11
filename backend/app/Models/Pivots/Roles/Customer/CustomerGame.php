<?php

namespace App\Models\Pivots\Roles\Customer;

use App\Models\Pivots\BasePivot;
use App\Models\Game;
use App\Models\Roles\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para permisos de juegos de clientes
 */
class CustomerGame extends BasePivot
{
    /**
     * El pivot no es autoincremental.
     */
    public $incrementing = false;

    /**
     * El pivot usa clave primaria compuesta.
     */
    protected $primaryKey = ['customer_id', 'game_id'];

    /**
     * Indica que la clave primaria es compuesta.
     */
    protected $keyType = 'string';

    protected $table = 'customers_games';

    protected $fillable = [
        'customer_id',
        'game_id',
        'status'
    ];

    protected $casts = [
        'status' => 'string',
        'customer_id' => 'string',
        'game_id' => 'string',
    ];

    /**
     * Relación con el cliente
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

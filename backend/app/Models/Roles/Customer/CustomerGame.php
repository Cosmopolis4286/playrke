<?php

namespace App\Models\Roles\Customer;

use App\Models\Pivots\BasePivot;
use App\Models\Roles\Customer;
use App\Models\Game;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerGame extends BasePivot
{
    /**
     * El pivot no es autoincremental y usa clave primaria compuesta.
     */
    public $incrementing = false;
    protected $primaryKey = ['customer_id', 'game_id'];
    protected $keyType = 'string';
    protected array $pivotIdentityKeys = ['customer_id', 'game_id'];

    protected $table = "customers_games";

    protected $fillable = [
        'customer_id',
        'game_id',
        'customer_game_id',
        'product',
        'description',
        'status'
    ];

    protected $casts = [
        'status' => 'string',
        'customer_id' => 'string',
        'game_id' => 'string',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}

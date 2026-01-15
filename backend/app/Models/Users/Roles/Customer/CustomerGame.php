<?php

namespace App\Models\Users\Customer;

use App\Models\Users\Customer\Customer;
use App\Models\Games\Game;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerGame extends Model
{
    use SnowflakeId;

    protected $table = "customers_games";

    protected $fillable = [
        'customer_id',
        'game_id',
        'customer_game_id',
        'product',
        'description',
        'status'
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

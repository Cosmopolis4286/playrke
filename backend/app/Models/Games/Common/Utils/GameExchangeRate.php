<?php

namespace App\Models\Games\Common\Utils;

use App\Models\Currency;
use App\Models\Game;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameExchangeRate extends Model
{
    use SnowflakeId;

    protected $casts = [
        'exchange_rate' => 'decimal:8',
        'room' => 'integer',
    ];

    protected $fillable = [
        'game_id',
        'room',
        'currency_id',
        'exchange_rate',
    ];

    protected $table = 'game_exchange_rates';

    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function game() : BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}

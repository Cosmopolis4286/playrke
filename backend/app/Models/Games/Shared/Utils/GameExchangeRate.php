<?php

namespace App\Models\Games\Shared\Utils;

use App\Models\Games\Game;
use App\Models\Currencies\Currency;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameExchangeRate extends Model
{
    use SnowflakeId;

    protected $table = "game_exchange_rates";

    protected $fillable = [
        'game_id',
        'room',
        'currency_id',
        'exchange_rate'
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:8',
        'room' => 'integer'
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}

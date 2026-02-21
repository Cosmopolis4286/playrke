<?php

namespace App\Models\Games\Bingo\Round;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para premios fijos por moneda de una ronda de bingo
 */
class BingoRoundPrize extends Model
{
    use SnowflakeId;

    protected $table = 'bingo_round_prizes';

    protected $fillable = [
        'bingo_round_id',
        'room_id',
        'currency_id',
        'prize_value',
        'prize_description',
    ];

    protected $casts = [
        'bingo_round_id' => 'string',
        'room_id'        => 'string',
        'currency_id'    => 'string',
        'prize_value'    => 'decimal:6',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(BingoRound::class, 'bingo_round_id');
    }
}

<?php

namespace App\Domains\Games\Bingo\Models\Round;

use App\Models\Prize;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoRoundPrize extends Model
{
    use SnowflakeId;

    protected $table = 'bingo_round_prizes';

    protected $fillable = [
        'bingo_round_id',
        'room_id',
        'currency_id',
        'prize_id',
        'prize_value',
        'prize_description',
    ];

    protected $casts = [
        'bingo_round_id' => 'string',
        'room_id'        => 'string',
        'currency_id'    => 'string',
        'prize_id'       => 'string',
        'prize_value'    => 'decimal:6',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(BingoRound::class, 'bingo_round_id');
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class, 'prize_id');
    }
}

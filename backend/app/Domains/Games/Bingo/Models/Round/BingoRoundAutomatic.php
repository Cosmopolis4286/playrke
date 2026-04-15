<?php

namespace App\Domains\Games\Bingo\Models\Round;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoRoundAutomatic extends Model
{
    use HasFactory, SnowflakeId;

    protected $table = 'bingo_rounds_automatic';

    protected $fillable = [
        'bingo_room_id',
        'automatic_bingo_room_id',
        'rounds',
        'prize_types',
        'bingo_patterns',
        'prize_values',
        'status'
    ];

    protected $casts = [
        'prize_types'    => 'array',
        'bingo_patterns' => 'array',
        'prize_values'   => 'array'
    ];

    public function bingoRoom(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'bingo_room_id');
    }

    public function automaticBingoRoom(): BelongsTo
    {
        return $this->belongsTo(BingoRoomAutomatic::class, 'automatic_bingo_room_id');
    }
}

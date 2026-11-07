<?php

namespace App\Domains\Games\Bingo\Models\Round;

use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomWinner;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BingoRound extends Model
{
    use SnowflakeId;

    protected $casts = [
        'room_id' => 'string',
        'room_config_id' => 'string',
        'round' => 'integer',
        'start_time' => 'datetime',
        'bingo_pattern_id' => 'string',
    ];

    protected $fillable = [
        'room_id',
        'room_config_id',
        'round',
        'start_time',
        'last_chip',
        'bingo_pattern_id',
        'prize_type',
        'status',
    ];

    protected $table = 'bingo_rounds';

    public function draws() : HasMany
    {
        return $this->hasMany(BingoDraw::class, 'round_id');
    }

    public function bingoPattern() : BelongsTo
    {
        return $this->belongsTo(BingoPattern::class, 'bingo_pattern_id');
    }

    public function prizes() : HasMany
    {
        return $this->hasMany(BingoRoundPrize::class, 'bingo_round_id');
    }

    public function room() : BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    public function roomConfig() : BelongsTo
    {
        return $this->belongsTo(BingoRoomConfig::class, 'room_config_id');
    }

    public function winners() : HasMany
    {
        return $this->hasMany(BingoRoomWinner::class, 'round_id');
    }
}

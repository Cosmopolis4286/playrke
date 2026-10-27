<?php

namespace App\Domains\Games\Bingo\Models\Room\User;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para ganadores de salas de bingo
 */
class BingoRoomWinner extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'room_id',
        'room_user_id',
        'round_id',
        'award_status',
    ];

    protected $table = 'bingo_rooms_winners';

    public function room() : BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    public function roomUser() : BelongsTo
    {
        return $this->belongsTo(BingoRoomUser::class, 'room_user_id');
    }

    public function round() : BelongsTo
    {
        return $this->belongsTo(BingoRound::class, 'round_id');
    }
}

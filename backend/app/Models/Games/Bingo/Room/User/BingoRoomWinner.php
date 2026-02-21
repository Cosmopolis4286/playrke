<?php

namespace App\Models\Games\Bingo\Room\User;

use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Bingo\Round\BingoRound;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para ganadores de salas de bingo
 */
class BingoRoomWinner extends Model
{
    use SnowflakeId;

    protected $table = 'bingo_rooms_winners';

    protected $fillable = [
        'room_id',
        'room_user_id',
        'round_id',
        'award_status'
    ];

    /**
     * Relación con la sala de bingo
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Relación con el jugador de la sala
     */
    public function roomUser(): BelongsTo
    {
        return $this->belongsTo(BingoRoomUser::class, 'room_user_id');
    }

    /**
     * Relación con la ronda
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(BingoRound::class, 'round_id');
    }
}

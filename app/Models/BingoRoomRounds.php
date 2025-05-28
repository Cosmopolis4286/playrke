<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoomRounds extends Model
{
    protected $table = 'bingo_rooms_rounds';
    protected $fillable = ['room', 'round', 'type_play', 'coin', 'pct', 'prize_amount', 'status'];

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getByRound($room, $round, $fields = ['*'])
    {
        return self::select($fields)->where('room', $room)->where('round', $round)->first();
    }

    public static function getRoundInProgress($room)
    {
        return self::where('room', $room)->where('status', 1)->value('round');
    }
}

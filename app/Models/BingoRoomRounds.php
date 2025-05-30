<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoomRounds extends Model
{
    protected $table = 'bingo_rooms_rounds';
    protected $fillable = ['room', 'round', 'play_type', 'prize_type', 'prize_value', 'status'];

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
        return self::where('room', $room)->where('status', 'IN PROGRESS')->value('round');
    }

    public static function existsRounds($room)
    {
        return self::where('room', $room)->exists();
    }
}

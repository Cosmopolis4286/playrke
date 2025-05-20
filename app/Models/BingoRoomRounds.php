<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoomRounds extends Model
{
    protected $table = 'bingo_rooms_rounds';
    protected $fillable = ['room', 'round_num', 'status'];

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getActiveRound($room)
    {
        return self::where('room', $room)->where('status', 1)->value('round_num');
    }
}

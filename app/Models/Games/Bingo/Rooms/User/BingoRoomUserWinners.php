<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoomUserWinners extends Model
{
    protected $table = 'bingo_rooms_user_winners';
    protected $fillable = ['room', 'room_user', 'round', 'status'];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function Player()
    {
        return $this->belongsTo(BingoRoomUser::class, 'room_user');
    }

    public function Round()
    {
        return $this->belongsTo(BingoRounds::class, 'round');
    }

    public static function getById($room, $fields = ['*'])
    {
        return self::select($fields)->where('id', $room)->first();
    }

    public static function getValue($id, $value)
    {
        return self::where('id', $id)->value($value);
    }
}

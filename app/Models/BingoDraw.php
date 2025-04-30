<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoDraw extends Model
{
    protected $table = 'bingo_draw';
    protected $fillable = ['chip', 'code', 'room', 'time', 'status', 'created_at', 'updated_at'];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function CodeDraw()
    {
        return $this->belongsTo(BingoDrawCode::class, 'room', 'room');
    }

    public static function getWinningChip($room)
    {
        return self::where('room', $room)->where('status', 3)->first();
    }

    public static function getRoomDraw($room)
    {
        return self::where('room', $room)->get();
    }

    public static function getFirstDrawChip($room, $fields = ['*'])
    {
        return self::select($fields)->where('room', $room)->first();
    }
}

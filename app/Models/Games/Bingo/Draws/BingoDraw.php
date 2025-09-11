<?php

namespace App\Models;

use App\Models\Games\Bingo\Rooms\BingoRoom;
use Illuminate\Database\Eloquent\Model;

class BingoDraw extends Model
{
    protected $table = 'bingo_draws';
    protected $fillable = ['chip', 'code', 'room', 'round', 'time', 'status', 'created_at', 'updated_at'];

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
        return self::where('room', $room)->where('status', 3)->orderByDesc('id')->first();
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

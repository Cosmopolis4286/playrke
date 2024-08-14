<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoSort extends Model
{
    protected $table = 'bingo_sort';
    protected $fillable = ['chip', 'code', 'game_mode', 'room', 'time', 'win', 'status'];

    public function GameMode()
    {
        return $this->belongsTo(GameModes::class, 'game_mode', 'game_mode');
    }

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function CodeSort()
    {
        return $this->belongsTo(BingoSortCode::class, 'room', 'room');
    }

    public static function getWinningChip($room)
    {
        return self::where('room', $room)->where('win', 1)->first();
    }

    public static function getRoomSort($room)
    {
        return self::where('room', $room)->get();
    }

    public static function getFirstSortChip($room, $fields = ['*'])
    {
        return self::where('room', $room)->select($fields)->first();
    }
}

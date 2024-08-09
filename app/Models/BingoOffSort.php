<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoOffSort extends Model
{
    protected $table = 'bingo_off_sort';
    protected $fillable = ['chip', 'code', 'room', 'time', 'win', 'status'];

    public function Room()
    {
        return $this->belongsTo(BingoOffRoom::class, 'room');
    }

    public function CodeSort()
    {
        return $this->belongsTo(BingoOffSortCode::class, 'room', 'room');
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

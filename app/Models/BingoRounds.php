<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRounds extends Model
{
    protected $table = 'bingo_rounds';
    protected $fillable = ['room', 'round', 'play_type', 'prize_type', 'prize_value', 'status'];

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getByRoom($room, $round = '', $fields = ['*'])
    {
        $query = self::select($fields)->where('room', $room);

        if ($round) {
            $query->where('round', $round);
        }

        return $query->first();
    }

    public static function getRoundInProgress($room, $fields = ['*'])
    {
        return self::select($fields)->where('room', $room)->where('status', 'IN PROGRESS')->first();
    }

    public static function getValueByRoundInProgress($room, $value)
    {
        return self::where('room', $room)->where('status', 'IN PROGRESS')->value($value);
    }

    public static function existsRounds($room)
    {
        return self::where('room', $room)->exists();
    }
}

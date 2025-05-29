<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoomRoundsDrafts extends Model
{
    protected $table = 'bingo_rooms_rounds_drafts';
    protected $fillable = ['room', 'num_round', 'prize_types', 'play_types', 'prize_values', 'status'];

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }
}

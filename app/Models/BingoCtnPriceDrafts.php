<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCtnPriceDrafts extends Model
{
    protected $table = 'bingo_ctn_price_drafts';
    protected $fillable = ['bingo_room_id', 'automatic_bingo_room_id', 'creator_user', 'coins', 'ctn_prices', 'status'];

    protected $casts = ['coins'  => 'array', 'ctn_prices' => 'array'];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'bingo_room_id');
    }

    public function AutomaticRoom()
    {
        return $this->belongsTo(BingoRoomAutomatic::class, 'automatic_bingo_room_id');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getByRoom($room, $fields = ['*'])
    {
        return self::select($fields)->where('bingo_room_id', $room)->first();
    }

    public static function getValueByRoom($room, $value)
    {
        return self::where('bingo_room_id', $room)->value($value);
    }

    public static function getByAutomaticRoom($automaticRoom, $fields = ['*'])
    {
        return self::select($fields)->where('automatic_bingo_room_id', $automaticRoom)->first();
    }

    public static function getValueByAutomaticRoom($automaticRoom, $value)
    {
        return self::where('automatic_bingo_room_id', $automaticRoom)->value($value);
    }
}

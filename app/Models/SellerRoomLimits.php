<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerRoomLimits extends Model
{
    protected $table = "sellers_room_limits";
    protected $fillable = ['game', 'room', 'seller', 'commn'];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'seller');
    }

    public static function getGameRoomValueBySeller($game, $room, $seller, $value)
    {
        return self::where('game', $game)->where('room', $room)->where('seller', $seller)->value($value);
    }
}

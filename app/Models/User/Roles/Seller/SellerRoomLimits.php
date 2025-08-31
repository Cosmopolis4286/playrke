<?php

namespace App\Models\User\Roles\Seller;

use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models\Games\Shared\Games;
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
        $gameID = Games::getValueByName($game);
        return self::where('game_id', $gameID)->where('room_id', $room)->where('seller_id', $seller)->value($value);
    }
}

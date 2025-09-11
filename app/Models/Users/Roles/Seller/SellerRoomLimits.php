<?php

namespace App\Models\Users\Roles\Seller;

use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models\Games\Shared\Games;
use Illuminate\Database\Eloquent\Model;

class SellerRoomLimits extends Model
{
    protected $table = "sellers_room_limits";
    protected $fillable = ['game', 'room', 'seller', 'commission'];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public static function getGameRoomValueBySeller($gameId, $roomId, $sellerId, $value)
    {
        $gameId = Game::getValueByName($game);
        return self::where('game_id', $gameId)->where('room_id', $room)->where('seller_id', $seller)->value($value);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoOffRoomUser extends Model
{
    protected $table = 'bingo_off_rooms_user';
    protected $primaryKey = 'id';
    protected $fillable = ['room', 'user', 'player_link', 'provider', 'commerce', 'seller', 'winner'];

    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'seller');
    }

    public function Room()
    {
        return $this->belongsTo(BingoOffRoom::class, 'room');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public function ComRoom()
    {
        return $this->belongsTo(SellerRoomLimits::class, 'seller', 'seller');
    }

    public function PlayerLink()
    {
        return $this->belongsTo(PlayerLink::class, 'player_link', 'id');
    }

    public function WinCtns()
    {
        return $this->hasMany(BingoOffPrizeCtns::class, 'player_link', 'player_link');
    }

    public static function getPlayerCountByRoom($room, $condition = ['!=', ''])
    {
        // Verifica que el formato de la condición sea válido
        if (!is_array($condition) || count($condition) !== 2) {
            throw new \InvalidArgumentException("La condición debe ser un arreglo con dos elementos: [operador, valor].");
        }

        list($operator, $value) = $condition;

        return self::where('room', $room)->where('winner', $operator, $value)->count();
    }

    public static function getWinnersCountByRoom($room)
    {
        return self::where('room', $room)->where('winner', '>', 0)->count();
    }

    public static function getNonWinnersCountByRoom($room)
    {
        return self::where('room', $room)->where('winner', 0)->count();
    }
}

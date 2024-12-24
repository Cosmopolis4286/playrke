<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoomUser extends Model
{
    protected $table = 'bingo_rooms_user';
    protected $fillable = [
        'room',
        'customer',
        'commerce',
        'subsidiary',
        'seller',
        'player_user',
        'player_link',
        'coin',
        'ctns',
        'automarker',
        'winner'
    ];

    public function Room()
    {
        return $this->belongsTo(BingoRoomUser::class, 'room');
    }

    public function Customer()
    {
        return $this->belongsTo(Customer::class, 'customer');
    }

    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce');
    }

    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'seller');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public function ComRoom()
    {
        return $this->belongsTo(SellerRoomLimits::class, 'seller', 'seller');
    }

    public function CustomerUser()
    {
        return $this->belongsTo(CustomerUser::class, 'player_user', 'id');
    }

    public function PlayerUser()
    {
        return $this->belongsTo(User::class, 'player_user', 'id');
    }

    public function PlayerLink()
    {
        return $this->belongsTo(PlayerLink::class, 'player_link', 'id');
    }

    public function PrizeCtns()
    {
        return $this->hasMany(BingoPrizeCtns::class, 'room_user', 'id');
    }

    public static function getValue($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getByColumn($column, $value, $fields = ['*'])
    {
        return self::select($fields)->where($column, $value)->first();
    }

    public static function getValueByColumn($room, $column, $columValue, $value)
    {
        return self::where('room', $room)->where($column, $columValue)->value($value);
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

    public static function existsPlayerInRoom($room, $creator, $player)
    {
        return self::where('room', $room)->where($creator[0], $creator[1])
            ->where($player[0], $player[1])->exists();
    }
}

<?php

namespace App\Models\User\Roles\Seller;

use App\Models\Currencies\Currencys\Coin;
use App\Models\Countries\Country;
use App\Models\User\Roles\Commerce\Commerce;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    protected $table = "sellers";
    protected $fillable = ['name', 'country', 'currency', 'phone', 'commission', 'commerce_id', 'status', 'user'];

    // Relación con el país del vendedor
    public function Country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    // Relación con la moneda del vendedor
    public function Coin()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    public static function getCoinByIdId($seller)
    {
        return self::where('id', $seller)->value('currency');
    }

    // Relación del Vendedor con las Monedas  
    public function CountriesCoins()
    {
        return $this->hasMany(SellerCountriesCoins::class, 'seller', 'id');
    }

    // Relación con la Comision del vendedor en la sala
    public function ComSellerRoom()
    {
        return $this->belongsTo(SellerRoomLimits::class, 'id', 'seller');
    }

    // Relación con el comercio del vendedor
    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
    }

    // Relación con los juegos de los jugadores
    public function PlayerGames()
    {
        return $this->hasMany(BingoRoomUser::class, 'seller', 'id');
    }

    // Relación con los cartones del vendedor
    public function Ctns()
    {
        return $this->hasMany(BingoCtns::class, 'seller', 'id');
    }

    // Relación con los estados de los cartones del vendedor
    public function CtnsLot()
    {
        return $this->hasMany(BingoCtnsLot::class, 'seller', 'id');
    }

    // Relación con el usuario del vendedor
    public function User()
    {
        return $this->belongsTo(User::class, 'user');
    }

    static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getByUserId($user, $fields = ['*'])
    {
        return self::select($fields)->where('user', $user)->first();
    }

    static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getValueByUserId($user, $value = 'id')
    {
        return self::where('user', $user)->value($value);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerCountriesCoins extends Model
{
    protected $table = "sellers_countries_coins";
    protected $fillable = ['seller', 'country', 'coin', 'status', 'created_at', 'updated_at'];

    // Relación con el Pais 
    public function Country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    // Relación con la moneda del vendedor
    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    // Relación con el vendedor
    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'seller');
    }

    public static function getById($seller, $coin, $fields = ['*'])
    {
        return self::select($fields)->where('seller', $seller)->where('coin', $coin)->first();
    }

    public static function getValue($seller, $coin, $value)
    {
        return self::where('seller', $seller)->where('coin', $coin)->value($value);
    }

    public static function getCountCoin($seller)
    {
        return self::where('seller', $seller)->distinct('coin')->count('coin');
    }

    public static function getAvailableCountries($seller)
    {
        return self::where('seller', $seller)->where('status', 1)
            ->distinct('country')->pluck('country')->toArray();
    }

    public static function getAvailableCoins($seller)
    {
        return self::where('seller', $seller)->where('status', 1)
            ->distinct('coin')->pluck('coin')->toArray();
    }

    public static function getCountryCoin($seller, $arr, $fields = ['*'])
    {
        return self::select($fields)->where('seller', $seller)
            ->where('country', $arr[0])->where($arr[1])->first();
    }

    public static function getCountryCoinValue($seller, $arr, $value)
    {
        return self::where('seller', $seller)->where('country', $arr[0])->where($arr[1])->value($value);
    }

    public static function getCountCountryCoin($seller)
    {
        return self::where('seller', $seller)->count();
    }
}

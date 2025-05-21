<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coin extends Model
{
    protected $table = "coins";
    protected $fillable = ['iso_code', 'symbol', 'short_name', 'long_name', 'country', 'status'];

    public function Country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public function ExchangeRate()
    {
        return $this->belongsTo(ExchangeRate::class, 'id', 'coin');
    }

    public function CommerceExchangeRate()
    {
        return $this->hasMany(CommerceExchangeRate::class, 'coin', 'id');
    }

    public function RoomBingo()
    {
        return $this->belongsTo(BingoRoom::class, 'id', 'coin');
    }

    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'id', 'coin');
    }

    public static function getById($coin, $fields = ['*'])
    {
        return self::select($fields)->where('id', $coin)->first();
    }

    public static function getByISOCode($ISOCode, $fields = ['*'])
    {
        return self::select($fields)->where('iso_code', $ISOCode)->first();
    }

    public static function getByIdForStatus($status = ['>=', 0], $fields = ['*'])
    {
        // Asume que $status es un array con dos elementos: operador y valor
        return self::select($fields)->where('status', $status[0], $status[1])->get();
    }

    public static function getValue($coin, $value)
    {
        return self::where('id', $coin)->value($value);
    }

    public static function getValueByISOCode($ISOCode, $value)
    {
        return self::where('iso_code', $ISOCode)->value($value);
    }

    public static function getCoinByCountry($country, $fields = ['*'])
    {
        return self::select($fields)->where('country', $country)->first();
    }

    public static function getValueByCountry($country, $value)
    {
        return self::where('country', $country)->value($value);
    }

    public static function getValueBySymbol($symbol, $value)
    {
        return self::where('symbol', $symbol)->value($value);
    }

    public static function getByIdIdOfCountry($country)
    {
        return self::where('country', $country)->value('id');
    }

    public static function getCoinsById($arrCoins, $fields = ['*'])
    {
        return self::select($fields)->whereIn('id', $arrCoins)->get();
    }

    public static function getCoinsInArray()
    {
        return self::distinct()->pluck('coin')->toArray();
    }

    public static function getCoinsByCountry($countries)
    {
        return self::select('id')->whereIn('country', $countries)->distinct()->pluck('id')->toArray();
    }

    public static function getCountriesByCoinInArray($coins)
    {
        return self::whereIn('id', $coins)->pluck('country')->toArray();
    }

    public static function getPaymentMethodByCoin($coin)
    {
        return self::where('id', $coin)->value('payment_method');
    }

    public static function getAvailableCoins($fields = ['*'])
    {
        return self::select($fields)->where('status', 1)->get();
    }
}

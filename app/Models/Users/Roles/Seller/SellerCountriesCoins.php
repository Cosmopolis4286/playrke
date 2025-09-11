<?php

namespace App\Models\Users\Roles\Seller;

use App\Models\Currencies\Currency;
use App\Models\Countries\Country;
use Illuminate\Database\Eloquent\Model;

class SellerCountriesCurrencies extends Model
{
    protected $table = "sellers_countries_currencies";
    protected $fillable = ['seller', 'country_id', 'currency_id', 'level', 'creator_user_id', 'status'];

    // Relación con el Pais 
    public function Country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    // Relación con la moneda del vendedor
    public function Currency()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    // Relación con el vendedor
    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'seller');
    }

    public static function getById($seller, $currency, $fields = ['*'])
    {
        return self::select($fields)->where('seller', $seller)->where('currency', $currency)->first();
    }

    public static function getValue($seller, $currency, $value)
    {
        return self::where('seller', $seller)->where('currency', $currency)->value($value);
    }

    public static function getCountCurrency($seller)
    {
        return self::select('currency')->where('seller', $seller)
            ->whereNotNull('currency')->distinct()->count();
    }

    public static function getAvailableCountries($seller)
    {
        return self::select('country')->where('seller', $seller)->where('status', 1)
            ->distinct()->pluck('country')->toArray();
    }

    public static function getAvailableCurrencies($seller)
    {
        return self::select('currency')->where('seller', $seller)
            ->where('status', 1)->distinct()->pluck('currency')->toArray();
    }

    public static function getCountryCurrency($seller, $arr, $fields = ['*'])
    {
        return self::select($fields)->where('seller', $seller)
            ->where('country', $arr[0])->where($arr[1])->first();
    }

    public static function getCountryCurrencyValue($seller, $arr, $value)
    {
        return self::where('seller', $seller)->where('country', $arr[0])->where($arr[1])->value($value);
    }

    public static function getCountCountryCurrency($seller)
    {
        return self::where('seller', $seller)->count();
    }
}

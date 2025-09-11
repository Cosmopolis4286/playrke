<?php

namespace App\Models\User\Roles\Treasurer;

use Illuminate\Database\Eloquent\Model;

class TreasurerCountriesCurrencies extends Model
{
    protected $table = "treasurers_countries_currencies";
    protected $fillable = ['treasurer', 'country', 'currency', 'level', 'creator_user_id', 'status'];

    public function Treasurer()
    {
        return $this->belongsTo(Treasurer::class, 'treasurer');
    }

    public function Country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public function Currency()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    public static function getAvailableCurrencies($treasurer, $exceptCurrency = '')
    {
        return self::select('currency')->where('treasurer', $treasurer)
            ->where('status', 1)
            ->when($exceptCurrency, function ($query, $exceptCurrency) {
                return $query->where('currency', '<>', $exceptCurrency);
            })->distinct()->pluck('currency')->toArray();
    }

    public static function getAllAvailableCountriesCurrencies($treasurer, $fields = ['*'])
    {
        return self::select($fields)->where('treasurer', $treasurer)->where('status', 1)->orderBy('country')->get();
    }

    public static function getAvailableCountries($treasurer)
    {
        return self::select('country')->where('treasurer', $treasurer)
            ->where('status', 1)->distinct()->pluck('country')->toArray();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasurerCountriesCoins extends Model
{
    protected $table = "treasurers_countries_coins";
    protected $fillable = ['treasurer', 'country', 'coin', 'creator_user', 'status'];

    public function Treasurer()
    {
        return $this->belongsTo(Treasurer::class, 'treasurer');
    }

    public function Country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public static function getAvailableCoins($treasurer, $exceptCoin = '')
    {
        return self::select('coin')->where('treasurer', $treasurer)
            ->where('status', 1)
            ->when($exceptCoin, function ($query, $exceptCoin) {
                return $query->where('coin', '<>', $exceptCoin);
            })->distinct()->pluck('coin')->toArray();
    }

    public static function getAllAvailableCountriesCoins($treasurer, $fields = ['*'])
    {
        return self::select($fields)->where('treasurer', $treasurer)->where('status', 1)->orderBy('country')->get();
    }

    public static function getAvailableCountries($treasurer)
    {
        return self::select('country')->where('treasurer', $treasurer)
            ->where('status', 1)->distinct()->pluck('country')->toArray();
    }
}

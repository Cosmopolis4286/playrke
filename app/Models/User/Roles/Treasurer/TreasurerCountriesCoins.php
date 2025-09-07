<?php

namespace App\Models\User\Roles\Treasurer;

use Illuminate\Database\Eloquent\Model;

class TreasurerCountriesCoins extends Model
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

    public function Coin()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    public static function getAvailableCoins($treasurer, $exceptCoin = '')
    {
        return self::select('currency')->where('treasurer', $treasurer)
            ->where('status', 1)
            ->when($exceptCoin, function ($query, $exceptCoin) {
                return $query->where('currency', '<>', $exceptCoin);
            })->distinct()->pluck('currency')->toArray();
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

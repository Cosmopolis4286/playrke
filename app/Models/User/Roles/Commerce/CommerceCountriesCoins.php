<?php

namespace App\Models\User\Roles\Commerce;

use App\Models\Countries\Country;
use Illuminate\Database\Eloquent\Model;

class CommerceCountriesCoins extends Model
{
    protected $table = "commerces_countries_coins";
    protected $fillable = ['commerce_id', 'country', 'coin', 'level', 'creator_user', 'status'];

    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
    }

    public function Country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public function ExternalPlayers()
    {
        return $this->hasMany(ExternalPlayers::class, 'creator_user', 'creator_user');
    }

    public function CtnsLot()
    {
        return $this->hasMany(BingoCtnsLot::class, 'creator_user', 'creator_user');
    }

    public function PrizeCtns()
    {
        return $this->hasMany(BingoPrizeCtns::class, 'creator_user', 'creator_user');
    }

    public static function getAvailableCoins($commerce, $exceptCoin = '')
    {
        return self::select('coin')->where('commerce_id', $commerce)
            ->where('status', 1)
            ->when($exceptCoin, function ($query, $exceptCoin) {
                return $query->where('coin', '<>', $exceptCoin);
            })->distinct()->pluck('coin')->toArray();
    }

    public static function getAllAvailableCountriesCoins($commerce, $fields = ['*'])
    {
        return self::select($fields)->where('commerce_id', $commerce)->where('status', 1)->orderBy('country')->get();
    }

    public static function getAvailableCountries($commerce)
    {
        return self::select('country')->where('commerce_id', $commerce)
            ->where('status', 1)->distinct()->pluck('country')->toArray();
    }
}

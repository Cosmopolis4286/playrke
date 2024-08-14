<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommerceCountriesCoins extends Model
{
    protected $table = "commerces_countries_coins";
    protected $fillable = ['commerce', 'country', 'coin', 'status'];

    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce');
    }

    public function Country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public function PlayerLink()
    {
        return $this->hasMany(PlayerLink::class, 'commerce', 'commerce');
    }

    public function CtnsLot()
    {
        return $this->hasMany(BingoCtnsLot::class, 'commerce', 'commerce');
    }

    public static function getAvailableCoins($commerce)
    {
        return self::where('commerce', $commerce)->where('status', 1)
            ->distinct('coin')->pluck('coin')->toArray();
    }

    public static function getAllAvailableCountriesCoins($commerce, $fields = ['*'])
    {
        return self::select($fields)->where('commerce', $commerce)->where('status', 1)->orderBy('country')->get();
    }

    public static function getAvailableCountries($commerce)
    {
        return self::where('commerce', $commerce)->where('status', 1)
            ->distinct('country')->pluck('country')->toArray();
    }
}

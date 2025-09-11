<?php

namespace App\Models\User\Roles\Commerce;

use App\Models\Countries\Country;
use Illuminate\Database\Eloquent\Model;

class CommerceCountriesCurrencies extends Model
{
    protected $table = "commerces_countries_currencies";
    protected $fillable = ['commerce_id', 'country', 'currency', 'level', 'creator_user_id', 'status'];

    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
    }

    public function Country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public function Currency()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    public function ExternalPlayers()
    {
        return $this->hasMany(ExternalPlayers::class, 'creator_user_id', 'creator_user_id');
    }

    public function CtnsLot()
    {
        return $this->hasMany(BingoCtnsLot::class, 'creator_user_id', 'creator_user_id');
    }

    public function PrizeCtns()
    {
        return $this->hasMany(BingoPrizeCtns::class, 'creator_user_id', 'creator_user_id');
    }

    public static function getAvailableCurrencies($commerce, $exceptCurrency = '')
    {
        return self::select('currency')->where('commerce_id', $commerce)
            ->where('status', 1)
            ->when($exceptCurrency, function ($query, $exceptCurrency) {
                return $query->where('currency', '<>', $exceptCurrency);
            })->distinct()->pluck('currency')->toArray();
    }

    public static function getAllAvailableCountriesCurrencies($commerce, $fields = ['*'])
    {
        return self::select($fields)->where('commerce_id', $commerce)->where('status', 1)->orderBy('country')->get();
    }

    public static function getAvailableCountries($commerce)
    {
        return self::select('country')->where('commerce_id', $commerce)
            ->where('status', 1)->distinct()->pluck('country')->toArray();
    }
}

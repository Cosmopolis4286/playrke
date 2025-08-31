<?php

namespace App\Models\Countries;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = "countries";
    protected $fillable = ['name', 'iso_code_2', 'iso_code_3', 'status'];

    public function Seller()
    {
        return $this->hasMany(Seller::class, 'id', 'country');
    }

    public function Player()
    {
        return $this->hasMany(PlayerLink::class, 'id', 'country');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getCountriesById($arr, $fields = ['*'])
    {
        return self::select($fields)->whereIn('id', $arr)->get();
    }

    public static function getValue($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getAllAvailableCountries($fields = ['*'])
    {
        return self::select($fields)->where('status', 1)->get();
    }

    public static function getCountryByIsoCode($isoCode, $number = '2')
    {
        return self::where('iso_code_' . $number, strtoupper($isoCode))->first();
    }

    public static function getCountries($arr, $fields = ['*'])
    {
        return self::select($fields)->where('status', 1)->whereIn('id', $arr)->get();
    }
}

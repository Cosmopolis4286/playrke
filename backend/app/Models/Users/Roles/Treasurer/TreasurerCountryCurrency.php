<?php

namespace App\Models\Users\Roles\Treasurer;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para relaciones tesorero-país-moneda.
 */
class TreasurerCountryCurrency extends Model
{
    use SnowflakeId;

    protected $table = "treasurers_countries_currencies";

    protected $fillable = [
        'treasurer',
        'country',
        'currency',
        'level',
        'creator_user_id',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'level' => 'integer'
    ];

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

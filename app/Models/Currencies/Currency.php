<?php

namespace App\Models\Currencies;

use App\Models\Countries\Country;
use App\Models\ExchangeRate\ExchangeRate;
use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models\Users\Roles\Commerce\CommerceExchangeRate;
use App\Models\Users\Roles\Seller\Seller;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table = "currencies";

    protected $fillable = [
        'iso_code',
        'symbol',
        'short_name',
        'long_name',
        'country_id',
        'status',
        'payment_method'
    ];

    /**
     * País asociado a la moneda.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Tasa de cambio actual de la moneda.
     */
    public function exchangeRate()
    {
        return $this->hasOne(ExchangeRate::class, 'currency_id');
    }

    /**
     * Tasas de cambio por comercio.
     */
    public function commerceExchangeRates()
    {
        return $this->hasMany(CommerceExchangeRate::class, 'currency_id');
    }

    /**
     * Vendedores que aceptan esta moneda.
     */
    public function sellers()
    {
        return $this->hasMany(Seller::class, 'currency_id');
    }
}

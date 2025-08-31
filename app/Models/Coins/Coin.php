<?php

namespace App\Models\Coins;

use App\Models\Countries\Country;
use App\Models\ExchangeRate\ExchangeRate;
use App\Models\User\Roles\Commerce\CommerceExchangeRate;
use App\Models\User\Roles\Seller\Seller;
use Illuminate\Database\Eloquent\Model;

class Coin extends Model
{
    protected $table = "coins";

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
        return $this->hasOne(ExchangeRate::class, 'coin_id');
    }

    /**
     * Tasas de cambio por comercio.
     */
    public function commerceExchangeRates()
    {
        return $this->hasMany(CommerceExchangeRate::class, 'coin_id');
    }

    /**
     * Salas de bingo que aceptan esta moneda.
     */
    public function bingoRooms()
    {
        return $this->hasMany(BingoRoom::class, 'coin_id');
    }

    /**
     * Vendedores que aceptan esta moneda.
     */
    public function sellers()
    {
        return $this->hasMany(Seller::class, 'coin_id');
    }
}

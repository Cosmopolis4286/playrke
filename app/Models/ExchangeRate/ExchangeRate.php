<?php

namespace App\Models\ExchangeRate;

use App\Models\Coins\Coin;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $table = "exchange_rates";

    protected $fillable = [
        'coin_id',
        'value'
    ];

    /**
     * Relación: un tipo de cambio pertenece a una moneda.
     */
    public function coin()
    {
        return $this->belongsTo(Coin::class, 'coin_id');
    }
}

<?php

namespace App\Models\ExchangeRate;

use App\Models\Currencies\Currency;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $table = "exchange_rates";

    protected $fillable = [
        'currency_id',
        'value'
    ];

    /**
     * Relación: un tipo de cambio pertenece a una moneda.
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}

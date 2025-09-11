<?php

namespace App\Models\ExchangeRate;

use App\Models\Currencies\Currency;
use App\Models\User\Roles\Commerce\Commerce;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class ExchangeRateRelation extends Model
{
    protected $table = "exchange_rate_relation";

    protected $fillable = [
        'currency_id',
        'status',
        'commerce_id',
        'user_id'
    ];

    /**
     * Relación: la relación pertenece a una moneda.
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relación: la relación pertenece a un comercio.
     */
    public function commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
    }

    /**
     * Relación: la relación pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

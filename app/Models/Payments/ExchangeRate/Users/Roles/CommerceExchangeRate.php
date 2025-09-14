<?php

namespace App\Models\ExchangeRate;

use App\Models\Currencies\Currency;
use App\Models\Users\Roles\Commerce\Commerce;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;

class CommerceExchangeRate extends Model
{
    protected $table = "commerces_exchange_rates";

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

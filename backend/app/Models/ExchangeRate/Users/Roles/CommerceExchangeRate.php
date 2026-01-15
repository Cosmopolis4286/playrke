<?php

namespace App\Models\Payments\ExchangeRate\Users\Roles;

use App\Models\Currencies\Currency;
use App\Models\Users\Commerce\Commerce;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CommerceExchangeRate extends Pivot
{
    public $incrementing = false;
    protected $primaryKey = null;
    protected $keyType = 'string';

    protected $table = "commerces_exchange_rates";

    protected $fillable = [
        'currency_id',
        'status',
        'commerce_id',
        'user_id'
    ];

    protected $casts = [
        'commerce_id' => 'string',
        'currency_id' => 'string',
        'user_id' => 'string'
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

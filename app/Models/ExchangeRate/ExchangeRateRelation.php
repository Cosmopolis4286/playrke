<?php

namespace App\Models\ExchangeRate;

use App\Models\Coins\Coin;
use App\Models\User\Roles\Commerce\Commerce;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class ExchangeRateRelation extends Model
{
    protected $table = "exchange_rate_relation";

    protected $fillable = [
        'coin_id',
        'status',
        'commerce_id',
        'user_id'
    ];

    /**
     * Relación: la relación pertenece a una moneda.
     */
    public function coin()
    {
        return $this->belongsTo(Coin::class, 'coin_id');
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRateRelation extends Model
{
    protected $table = "exchange_rate_relation";
    protected $fillable = ['coin', 'status', 'commerce', 'user'];

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce');
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public static function allowedCurrenciesByCommerce($user, $commerce)
    {
        return self::where('user', $user)->where('commerce', $commerce)->pluck('coin')->toArray();
    }
}

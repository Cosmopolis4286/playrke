<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRateRelation extends Model
{
    protected $table = "exchange_rate_relation";
    protected $fillable = ['coin', 'status', 'commerce_id', 'user'];

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public static function allowedCurrenciesByCommerce($user, $commerce)
    {
        return self::select('coin')->where('user', $user)
            ->where('commerce_id', $commerce)->distinct()->pluck('coin')->toArray();
    }
}

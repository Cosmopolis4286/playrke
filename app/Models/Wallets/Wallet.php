<?php

namespace App\Models\Wallets;

use App\Models\Currencies\Currencys\Coin;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $table = "wallets";
    protected $fillable = ['currency_id', 'balance', 'usdt', 'user_id'];

    // Relación con el usuario
    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación con la moneda (currency)
    public function Coin()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public static function getValue($userId, $value)
    {
        return self::where('user_id', $userId)->value($value);
    }
}

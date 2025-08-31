<?php

namespace App\Models\Wallets;

use App\Models\Coins\Coin;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $table = "wallets";
    protected $fillable = ['coin_id', 'balance', 'usdt', 'user_id'];

    // Relación con el usuario
    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación con la moneda (coin)
    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin_id');
    }

    public static function getValue($userId, $value)
    {
        return self::where('user_id', $userId)->value($value);
    }
}

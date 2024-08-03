<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $table = "wallets"; 
    protected $fillable = ['coin', 'balance', 'usdt', 'user']; 

    // Relación con el usuario
    public function User()
    {
        return $this->belongsTo(User::class, 'user'); // Relación pertenece a un usuario
    }

    // Relación con la moneda (coin)
    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin'); // Relación pertenece a una moneda (coin)
    }
}

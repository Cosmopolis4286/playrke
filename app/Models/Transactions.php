<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    protected $table = "transactions";
    protected $fillable = ['code', 'customer', 'game', 'game_mode', 'user_pvr', 'transaction_type', 'amount', 'status'];

    public static function existsCode($code, $customer)
    {
        return self::where('code', $code)->where('customer', $customer)->exists();
    }
}

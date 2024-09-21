<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    protected $table = "transactions";
    protected $fillable = ['customer', 'user_id', 'game', 'game_mode', 'bet_id', 'code', 'transaction_type', 'coin', 'amount', 'status'];

    public static function getValueByCode($code, $value)
    {
        return self::where('code', $code)->value($value);
    }

    public static function existsCode($code, $customer)
    {
        return self::where('code', $code)->where('customer', $customer)->exists();
    }
}

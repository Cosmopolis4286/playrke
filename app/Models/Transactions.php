<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    protected $table = "transactions";
    protected $fillable = ['code', 'game', 'move', 'user', 'provider'];

    public static function existsCode($code, $provider)
    {
        return self::where('code', $code)->where('provider', $provider)->exists();
    }
}

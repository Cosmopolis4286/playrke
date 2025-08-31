<?php

namespace App\Models\ExchangeRate;

use App\Models\Coins\Coin;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $table = "exchange_rates";
    protected $fillable = ['coin_id', 'value'];

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin_id');
    }

    public static function getLastValueByCoin($coinId)
    {
        return self::where('coin_id', $coinId)->latest()->value('value');
    }

    public static function getValue($coinId)
    {
        return self::where('coin_id', $coinId)->value('value');
    }
}

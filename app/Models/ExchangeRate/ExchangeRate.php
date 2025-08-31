<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $table = "exchange_rates";
    protected $fillable = ['coin', 'value'];

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public static function getLastValueByCoin($coin)
    {
        return self::where('coin', $coin)->latest()->value('value');
    }

    public static function getValue($coin)
    {
        return self::where('coin', $coin)->value('value');
    }
}

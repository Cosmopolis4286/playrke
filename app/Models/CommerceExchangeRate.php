<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommerceExchangeRate extends Model
{
    protected $table = "commerces_exchange_rates";
    protected $fillable = ['commerce', 'coin', 'exchange_rate', 'status'];

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::with('Coin')->where('id', $id)->select($fields)->first();
    }

    public static function getCommerceCoinValue($commerce, $coin, $value = 'exchange_rate')
    {
        return self::where('commerce', $commerce)->where('coin', $coin)->value($value);
    }

    public static function getAllExchangeRates()
    {
        return self::where('status', 1)->get(['coin', 'exchange_rate']);
    }

    public static function getValuesByCoin($coin)
    {
        return self::where('coin', $coin)->pluck('exchange_rate');
    }

    public static function getCoinsByCommerce($commerce, $status = '')
    {
        $query = self::where('commerce', $commerce);

        if ($status) $query->where('status', $status);

        return $query->pluck('coin')->unique()->toArray();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommerceExchangeRate extends Model
{
    protected $table = "commerces_exchange_rates";
    protected $fillable = ['commerce', 'coin', 'exchange_rate', 'pct', 'status'];

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
        return self::with('Coin')->select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value = 'exchange_rate')
    {
        return self::where('id', $id)->value($value);
    }

    public static function getAllExchangeRatesByCoin($commerce, $coin)
    {
        return self::where('commerce', $commerce)->where('coin', $coin)
            ->orderByDesc('id')->get(['coin', 'exchange_rate', 'pct']);
    }

    public static function getCommerceCoinValue($commerce, $coin, $value = 'exchange_rate')
    {
        return self::where('commerce', $commerce)->where('coin', $coin)->orderByDesc('id')->value($value);
    }

    public static function getAllExchangeRates($commerce)
    {
        return self::where('commerce', $commerce)->where('status', 1)
            ->orderByDesc('id')->get(['coin', 'exchange_rate']);
    }

    public static function getValuesByCoin($coin)
    {
        return self::select('exchange_rate')->where('coin', $coin)->pluck('exchange_rate');
    }

    public static function getAvailableExchangeRates($commerce)
    {
        return self::where('commerce', $commerce)->whereIn('status', [1, 4])->get();
    }

    public static function getCoinsByCommerce($commerce, $status = '')
    {
        $query = self::select('coin')->where('commerce', $commerce);

        if ($status) $query->where('status', $status);

        return $query->distinct()->pluck('coin')->toArray();
    }
}

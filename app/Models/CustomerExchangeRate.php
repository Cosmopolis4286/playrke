<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerExchangeRate extends Model
{
    protected $table = "customers_exchange_rates";
    protected $fillable = ['customer', 'coin', 'exchange_rate', 'status'];

    public function Customer()
    {
        return $this->belongsTo(Commerce::class, 'commerce');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::where('id', $id)->select($fields)->first();
    }

    public static function getCustomerCoinValue($commerce, $coin, $value = 'value')
    {
        return self::where('customer', $commerce)->where('coin', $coin)->value($value);
    }

    public static function getAllExchangeRates()
    {
        return self::where('status', 1)->get(['coin', 'exchange_rate']);
    }

    public static function getValuesByCoin($coin)
    {
        return self::where('coin', $coin)->pluck('value');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerExchangeRate extends Model
{
    protected $table = "customers_exchange_rates";
    protected $fillable = ['customer', 'coin', 'exchange_rate', 'status'];

    public function Customer()
    {
        return $this->belongsTo(Commerce::class, 'customer');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::where('id', $id)->select($fields)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getCustomerCoinValue($customer, $coin, $value = 'value')
    {
        return self::where('customer', $customer)->where('coin', $coin)->value($value);
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

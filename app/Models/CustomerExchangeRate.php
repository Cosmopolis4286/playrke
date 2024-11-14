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
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value = 'exchange_rate')
    {
        return self::where('id', $id)->value($value);
    }

    public static function getByCustomerCoin($customer, $coin, $fields = ['*'])
    {
        return self::select($fields)->where('customer', $customer)->where('coin', $coin)->first();
    }

    public static function getCustomerCoinValue($customer, $coin, $value = 'exchange_rate')
    {
        return self::where('customer', $customer)->where('coin', $coin)->value($value);
    }

    public static function getAllExchangeRates()
    {
        return self::where('status', 1)->get(['coin', 'exchange_rate']);
    }

    public static function getValuesByCoin($coin)
    {
        return self::where('coin', $coin)->pluck('exchange_rate');
    }

    public static function getCoinsByCustomer($customer, $status = '')
    {
        $query = self::where('customer', $customer);

        if ($status) $query->where('status', $status);

        return $query->pluck('coin')->unique()->toArray();
    }

    public static function getCoinsIDsByCustomer($customer, $status = 1)
    {
        return self::select('id', 'coin')->where('customer', $customer)->where('status', $status)->get();
    }

    public static function existsCoinByCustomer($customer, $coin)
    {
        return self::where(['customer' => $customer, 'coin' => $coin])->exists();
    }
}

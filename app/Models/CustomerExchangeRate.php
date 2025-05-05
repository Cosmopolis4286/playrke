<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerExchangeRate extends Model
{
    protected $table = "customers_exchange_rates";
    protected $fillable = ['customer', 'coin', 'exchange_rate', 'pct', 'status'];

    public function Customer()
    {
        return $this->belongsTo(Commerce::class, 'customer');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->where('status', 1)->orderByDesc('id')->first();
    }

    public static function getValueById($id, $value = 'exchange_rate')
    {
        return self::where('id', $id)->value($value);
    }

    public static function getAllExchangeRatesByCoin($customer, $coin)
    {
        return self::where('customer', $customer)->where('coin', $coin)
            ->orderByDesc('id')->get(['coin', 'exchange_rate', 'pct']);
    }

    public static function getByCustomerCoin($customer, $coin, $fields = ['*'])
    {
        return self::select($fields)->where('customer', $customer)
            ->where('coin', $coin)->orderByDesc('id')->first();
    }

    public static function getCustomerCoinValue($customer, $coin, $value = 'exchange_rate')
    {
        return self::where('customer', $customer)->where('coin', $coin)->orderByDesc('id')->value($value);
    }

    public static function getAllExchangeRates($customer)
    {
        return self::where('customer', $customer)->where('status', 1)
            ->orderByDesc('id')->get(['coin', 'exchange_rate']);
    }

    public static function getValuesByCoin($coin)
    {
        return self::select('exchange_rate')->where('coin', $coin)->pluck('exchange_rate');
    }

    public static function getAvailableExchangeRates($customer)
    {
        return self::where('customer', $customer)->whereIn('status', [0, 1])->get();
    }

    public static function getCoinsByCustomer($customer, $status = '')
    {
        $query = self::select('coin')->where('customer', $customer);

        if ($status) $query->where('status', $status);

        return $query->distinct()->pluck('coin')->toArray();
    }

    public static function getCoinsIDsByCustomer($customer, $status = 1)
    {
        return self::select('id', 'coin')->where('customer', $customer)->where('status', $status)->get();
    }

    public static function existsCoinByCustomer($customer, $coin)
    {
        return self::where(['customer' => $customer, 'coin' => $coin])->exists();
    }

    public static function updateOrCreateExchangeRate($customer, $coin, $amount, $pct = '')
    {
        $lastRate = self::where(['customer' => $customer, 'coin' => $coin])
            ->orderByDesc('id')->first();

        // Solo crear nuevo si no existe o si el valor cambió
        if (!$lastRate || $lastRate->exchange_rate != $amount) {

            // Desactivar la tasa anterior si existe
            if ($lastRate) {
                $lastRate->status = 2;
                $lastRate->save();
            }

            // Crear nueva tasa activa
            self::create([
                'customer' => $customer,
                'coin' => $coin,
                'exchange_rate' => $amount,
                'pct' => $pct,
                'status' => 1
            ]);
        }
    }
}

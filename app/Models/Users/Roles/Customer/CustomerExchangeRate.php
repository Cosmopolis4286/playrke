<?php

namespace App\Models\Users\Roles\Customer;

use App\Models\Users\Roles\Commerce\Commerce;
use Illuminate\Database\Eloquent\Model;

class CustomerExchangeRate extends Model
{
    protected $table = "customers_exchange_rates";
    protected $fillable = ['customer', 'currency', 'exchange_rate', 'percentage', 'status'];

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

    public static function getAllExchangeRatesByCurrency($customer, $currency)
    {
        return self::where('customer', $customer)->where('currency', $currency)
            ->orderByDesc('id')->get(['currency', 'exchange_rate', 'percentage']);
    }

    public static function getByCustomerCurrency($customer, $currency, $fields = ['*'])
    {
        return self::select($fields)->where('customer', $customer)
            ->where('currency', $currency)->orderByDesc('id')->first();
    }

    public static function getCustomerCurrencyValue($customer, $currency, $value = 'exchange_rate')
    {
        return self::where('customer', $customer)->where('currency', $currency)
            ->where('status', 'ACTIVE')->orderByDesc('id')->value($value);
    }

    public static function getAllExchangeRates($customer)
    {
        return self::where('customer', $customer)->where('status', 'ACTIVE')
            ->where('status', 'ACTIVE')->orderByDesc('id')->get(['currency', 'exchange_rate']);
    }

    public static function getValuesByCurrency($currency)
    {
        return self::select('exchange_rate')->where('currency', $currency)
            ->where('status', 'ACTIVE')->pluck('exchange_rate');
    }

    public static function getAvailableExchangeRates($customer)
    {
        return self::where('customer', $customer)->where('status', '<>', 'OLD')->get();
    }

    public static function getCurrenciesByCustomer($customer, $status = 'ACTIVE')
    {
        $query = self::select('currency')->where('customer', $customer);

        if ($status) $query->where('status', $status);

        return $query->distinct()->pluck('currency')->toArray();
    }

    public static function getCurrenciesIDsByCustomer($customer, $status = 'ACTIVE')
    {
        return self::select('id', 'currency')->where('customer', $customer)->where('status', $status)->get();
    }

    public static function existsCurrencyByCustomer($customer, $currency)
    {
        return self::where(['customer' => $customer, 'currency' => $currency])->exists();
    }

    public static function updateOrCreateExchangeRate($customer, $currency, $amount, $pct = '')
    {
        $lastRate = self::where(['customer' => $customer, 'currency' => $currency])
            ->where('status', 'ACTIVE')->orderByDesc('id')->first();

        // Solo crear nuevo si no existe o si el valor cambió
        if (!$lastRate || $lastRate->exchange_rate != $amount) {

            // Desactivar la tasa anterior si existe
            if ($lastRate) {
                $lastRate->status = 'OLD';
                $lastRate->save();
            }

            // Crear nueva tasa activa
            self::create([
                'customer' => $customer,
                'currency' => $currency,
                'exchange_rate' => $amount,
                'percentage' => $pct,
                'status' => 'ACTIVE'
            ]);
        }
    }
}

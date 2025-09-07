<?php

namespace App\Models\User\Roles\Commerce;

use App\Models\Currencies\Currency;
use Illuminate\Database\Eloquent\Model;

class CommerceExchangeRate extends Model
{
    protected $table = "commerces_exchange_rates";
    protected $fillable = ['commerce_id', 'currency', 'exchange_rate', 'percentage', 'status'];

    public function Currency()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::with('Currency')->select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value = 'exchange_rate')
    {
        return self::where('id', $id)->value($value);
    }

    public static function getAllExchangeRatesByCurrency($commerce, $currency)
    {
        return self::where('commerce_id', $commerce)->where('currency', $currency)
            ->where('status', 'ACTIVE')->orderByDesc('id')->get(['currency', 'exchange_rate', 'percentage']);
    }

    public static function getCommerceCurrencyValue($commerce, $currency, $value = 'exchange_rate')
    {
        return self::where('commerce_id', $commerce)->where('currency', $currency)
            ->where('status', 'ACTIVE')->orderByDesc('id')->value($value);
    }

    public static function getAllExchangeRates($commerce)
    {
        return self::where('commerce_id', $commerce)->where('status', 'ACTIVE')
            ->orderByDesc('id')->get(['currency', 'exchange_rate']);
    }

    public static function getValuesByCurrency($currency)
    {
        return self::select('exchange_rate')->where('currency', $currency)
            ->where('status', 'ACTIVE')->pluck('exchange_rate');
    }

    public static function getAvailableExchangeRates($commerce)
    {
        return self::where('commerce_id', $commerce)->where('status', '<>', 'OLD')->get();
    }

    public static function getCurrenciesByCommerce($commerce, $status = 'ACTIVE')
    {
        $query = self::select('currency')->where('commerce_id', $commerce);

        if ($status) $query->where('status', $status);

        return $query->distinct()->pluck('currency')->toArray();
    }

    public static function updateOrCreateExchangeRate($commerce, $currency, $amount, $pct = '')
    {
        $lastRate = self::where(['commerce_id' => $commerce, 'currency' => $currency])
            ->where('status', 'ACTIVE')->orderByDesc('id')->first();

        // Solo crear nuevo si no existe o si el valor cambió
        if (!$lastRate || $lastRate->exchange_rate != $amount) {

            // Desactivar la tasa anterior si existe
            if ($lastRate) {
                $lastRate->status = 'OLD';
                $lastRate->save();
            }

            $status = $amount > 0 ? 'ACTIVE' : 'INACTIVE';

            // Crear nueva tasa activa
            self::create([
                'commerce_id' => $commerce,
                'currency' => $currency,
                'exchange_rate' => $amount,
                'percentage' => $pct,
                'status' => $status
            ]);
        }
    }
}

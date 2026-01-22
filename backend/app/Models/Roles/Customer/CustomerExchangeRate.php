<?php

namespace App\Models\Roles\Customer;

use App\Models\Currency;
use App\Models\Roles\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para tasas de cambio de clientes
 */
class CustomerExchangeRate extends Model
{
    public $incrementing = false;
    protected $primaryKey = null;
    protected $keyType = 'string';

    protected $table = 'customers_exchange_rates';

    protected $fillable = [
        'customer_id',
        'currency_id',
        'exchange_rate',
        'percentage',
        'status'
    ];

    protected $casts = [
        'customer_id' => 'string',
        'currency_id' => 'string',
        'exchange_rate' => 'decimal:4',
        'percentage' => 'float'
    ];

    /**
     * Relación con el cliente
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Relación con la moneda
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}

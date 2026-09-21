<?php

namespace App\Models\Roles\Customer;

use App\Models\BaseModel;
use App\Models\Currency;
use App\Models\Roles\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para tasas de cambio de clientes
 *
 * Antes extendía Model directo con primaryKey=null y sin PK Snowflake —
 * la migración real (create_customers_exchange_rates_table) sí declara
 * $table->snowflake('id')->primary() y softDeletes(), así que create()
 * fallaba con "Field 'id' doesn't have a default value" (nunca se había
 * ejercitado este modelo en un flujo real hasta el fix de
 * ExchangeRateContextService::resolveExchangeRatesByRole() para 'customer').
 * BaseModel trae SnowflakeId + SoftDeletes + Auditable + HasHashedRouteKey,
 * mismo patrón que CommerceExchangeRate (tabla hermana, mismo esquema).
 */
class CustomerExchangeRate extends BaseModel
{
    protected $table = 'customers_exchange_rates';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'customer_id',
        'currency_id',
        'amount',
        'percentage',
        'status'
    ];

    protected $casts = [
        'id' => 'string',
        'customer_id' => 'string',
        'currency_id' => 'string',
        'amount' => 'decimal:4',
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

<?php

namespace App\Models\Users\Roles\Commerce;

use App\Models\Currencies\Currency;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para tasas de cambio de comercios
 */
class CommerceExchangeRate extends Model
{
    use SnowflakeId;

    protected $table = 'commerces_exchange_rates';

    protected $fillable = [
        'commerce_id',
        'currency_id',
        'exchange_rate',
        'percentage',
        'status'
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:4',
        'percentage' => 'float'
    ];

    /**
     * Relación con el comercio
     */
    public function commerce(): BelongsTo
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
    }

    /**
     * Relación con la moneda
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
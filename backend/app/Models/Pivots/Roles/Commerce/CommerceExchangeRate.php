<?php

namespace App\Models\Pivots\Roles\Commerce;

use App\Models\Pivots\BasePivot;
use App\Models\Currency;
use App\Models\Roles\Commerce;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para tasas de cambio de comercios
 */
class CommerceExchangeRate extends BasePivot
{
    public $incrementing = false;
    protected $primaryKey = null;
    protected $keyType = 'string';

    protected $table = 'commerces_exchange_rates';

    protected $fillable = [
        'commerce_id',
        'currency_id',
        'exchange_rate',
        'percentage',
        'status'
    ];

    protected $casts = [
        'commerce_id' => 'string',
        'currency_id' => 'string',
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

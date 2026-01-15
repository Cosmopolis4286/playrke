<?php

namespace App\Models\Users\Commerce;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Modelo pivot para monedas de comercios
 */
class CommerceCurrency extends Pivot
{
    public $incrementing = false;
    protected $primaryKey = null;
    protected $keyType = 'string';

    protected $table = 'commerces_currencies';

    protected $fillable = [
        'commerce_id',
        'currency_id',
        'level',
        'status'
    ];

    protected $casts = [
        'commerce_id' => 'string',
        'currency_id' => 'string',
        'level' => 'integer'
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

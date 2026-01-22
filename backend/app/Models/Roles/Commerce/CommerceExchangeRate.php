<?php

namespace App\Models\Roles\Commerce;

use App\Models\BaseModel;
use App\Models\Currency;
use App\Models\Roles\Commerce;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommerceExchangeRate extends BaseModel
{
    /**
     * Tabla asociada.
     */
    protected $table = 'commerces_exchange_rates';

    /**
     * Clave primaria.
     */
    protected $primaryKey = 'id';

    /**
     * Snowflake no es autoincremental.
     */
    public $incrementing = false;

    /**
     * Tipo de la clave primaria.
     */
    protected $keyType = 'bigint';

    /**
     * Atributos asignables masivos.
     */
    protected $fillable = [
        'id',
        'commerce_id',
        'currency_id',
        'amount',
        'percentage',
        'status',
    ];

    /**
     * Casts de atributos.
     */
    protected $casts = [
        'id' => 'bigint',
        'commerce_id' => 'string',
        'currency_id' => 'string',
        'amount' => 'decimal:4',
        'percentage' => 'float',
        'status' => 'string',
    ];

    /**
     * Indica que el modelo usa timestamps.
     */
    public $timestamps = true;

    /**
     * Relación con el comercio.
     */
    public function commerce(): BelongsTo
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
    }

    /**
     * Relación con la moneda.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}

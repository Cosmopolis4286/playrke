<?php

namespace App\Models\Roles\Commerce;

use App\Models\BaseModel;
use App\Models\Currency;
use App\Models\Roles\Commerce;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommerceExchangeRate extends BaseModel
{
    /**
     * Snowflake no es autoincremental.
     */
    public $incrementing = false;

    /**
     * Indica que el modelo usa timestamps.
     */
    public $timestamps = true;

    /**
     * Atributos calculados que deben incluirse en la serialización.
     */
    protected $appends = [
        'currency_name',
    ];

    /**
     * Casts de atributos.
     */
    protected $casts = [
        'id' => 'string',
        'commerce_id' => 'string',
        'currency_id' => 'string',
        'amount' => 'decimal:4',
        'percentage' => 'decimal:2',
        'status' => 'string',
    ];

    /**
     * Atributos asignables masivos.
     */
    protected $fillable = [
        'commerce_id',
        'currency_id',
        'amount',
        'percentage',
        'status',
    ];

    /**
     * Tipo de la clave primaria.
     *
     * Aunque el id sea bigint en base de datos, se maneja como string
     * para evitar problemas de precisión (JS/JSON/PHP).
     */
    protected $keyType = 'string';

    /**
     * Clave primaria.
     */
    protected $primaryKey = 'id';
    /**
     * Tabla asociada.
     */
    protected $table = 'commerces_exchange_rates';

    /**
     * Relación con el comercio.
     */
    public function commerce() : BelongsTo
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
    }

    /**
     * Relación con la moneda.
     */
    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Accessor para el nombre de la moneda.
     */
    public function getCurrencyNameAttribute() : string
    {
        return $this->currency?->name ?? 'Moneda no disponible';
    }
}

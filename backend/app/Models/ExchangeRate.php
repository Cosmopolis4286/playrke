<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Modelo para la gestión de tipos de cambio.
 *
 * Representa los tipos de cambio entre diferentes monedas del sistema,
 * con valores precisos y relaciones con las monedas correspondientes.
 */
class ExchangeRate extends BaseModel
{
    public const SOURCE_SYSTEM = 'system';

    /**
     * Valores válidos de `source` — quién generó esta fila: 'user' (acción
     * manual, incluyendo el botón "Consultar" que sincroniza en lote) o
     * 'system' (HandleExchangeRatesCommand, cronjob automático). Ver
     * migración add_source_to_exchange_rates_table.
     */
    public const SOURCE_USER = 'user';

    /**
     * Valores válidos de `status` — único lugar que los enumera (antes
     * repetidos como strings mágicos en el enum de la migración y en
     * ningún otro sitio, así que ExchangeRateRequest::rules() no podía
     * validar 'status' contra el conjunto real y aceptaba cualquier
     * string). Debe mantenerse sincronizado con el enum de la migración
     * 2024_01_01_000204_create_exchange_rates_table.
     */
    public const STATUSES = ['ACTIVE', 'INACTIVE', 'DELETED', 'OLD', 'DIRECTORY'];

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'currency_id' => 'string',
        'user_id' => 'string',
        'amount' => 'decimal:4',
        'percentage' => 'decimal:3',
        'status' => 'string',
        'source' => 'string',
    ];

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'currency_id',  // ID de la moneda
        'user_id',      // ID del usuario que registró/actualizó
        'amount',       // Valor del tipo de cambio
        'percentage',   // Porcentaje de subida/bajada
        'status',       // Estado de la tasa
        'source',       // Origen: 'user' o 'system' (ver SOURCE_*)
    ];
    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'exchange_rates';

    /**
     * Validar que el valor sea correcto.
     *
     * @param float $value Valor a validar
     */
    public static function isValidValue(float $value) : bool
    {
        return $value > 0 && $value <= 999999.9999;
    }

    /**
     * Relación con la moneda asociada.
     */
    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Scope para filtrar tasas activas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope para filtrar por moneda.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCurrency($query, string $currencyId)
    {
        return $query->where('currency_id', $currencyId);
    }

    /**
     * Mutator para validar el valor antes de guardar.
     */
    public function setAmountAttribute(float $value) : void
    {
        if (! self::isValidValue($value)) {
            throw new InvalidArgumentException('El valor del tipo de cambio debe estar entre 0.0001 y 999999.9999');
        }

        $this->attributes['amount'] = $value;
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

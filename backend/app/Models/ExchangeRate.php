<?php

namespace App\Models;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para la gestión de tipos de cambio.
 *
 * Representa los tipos de cambio entre diferentes monedas del sistema,
 * con valores precisos y relaciones con las monedas correspondientes.
 */
class ExchangeRate extends BaseModel
{
    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'exchange_rates';

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
    ];

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
        'status' => 'string'
    ];

    /**
     * Relación con la moneda asociada.
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope para filtrar por moneda.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $currencyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCurrency($query, string $currencyId)
    {
        return $query->where('currency_id', $currencyId);
    }

    /**
     * Scope para filtrar por rango de valores.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minValue
     * @param float $maxValue
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByValueRange($query, float $minValue, float $maxValue)
    {
        return $query->whereBetween('amount', [$minValue, $maxValue]);
    }

    /**
     * Scope para obtener tipos de cambio altos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $threshold Umbral mínimo
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighValue($query, float $threshold = 100.0)
    {
        return $query->where('amount', '>', $threshold);
    }

    /**
     * Scope para obtener tipos de cambio bajos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $threshold Umbral máximo
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowValue($query, float $threshold = 1.0)
    {
        return $query->where('amount', '<', $threshold);
    }

    /**
     * Scope para ordenar por valor.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction Dirección del ordenamiento (asc|desc)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByValue($query, string $direction = 'desc')
    {
        return $query->orderBy('amount', $direction);
    }

    /**
     * Scope para tipos de cambio actualizados recientemente.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days Días hacia atrás
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentlyUpdated($query, int $days = 7)
    {
        return $query->where('updated_at', '>=', now()->subDays($days));
    }

    /**
     * Obtener el código de la moneda asociada.
     *
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->currency->code ?? 'Sin moneda';
    }

    /**
     * Obtener el nombre de la moneda asociada.
     *
     * @return string
     */
    public function getCurrencyName(): string
    {
        return $this->currency->name ?? 'Sin moneda';
    }

    /**
     * Obtener el valor formateado del tipo de cambio.
     *
     * @return string
     */
    public function getFormattedValue(): string
    {
        return number_format($this->amount, 4);
    }

    /**
     * Verificar si el valor está en un rango alto.
     *
     * @param float $threshold Umbral para considerar alto (por defecto 100)
     * @return bool
     */
    public function isHighValue(float $threshold = 100.0): bool
    {
        return $this->amount > $threshold;
    }

    /**
     * Verificar si el valor está en un rango bajo.
     *
     * @param float $threshold Umbral para considerar bajo (por defecto 1)
     * @return bool
     */
    public function isLowValue(float $threshold = 1.0): bool
    {
        return $this->amount < $threshold;
    }

    /**
     * Verificar si el valor es válido.
     *
     * @return bool
     */
    public function hasValidValue(): bool
    {
        return $this->amount > 0 && $this->amount <= 999999.9999;
    }

    /**
     * Calcular conversión de una cantidad usando este tipo de cambio.
     *
     * @param float $amount Cantidad a convertir
     * @param bool $inverse Si usar conversión inversa
     * @return float
     */
    public function convert(float $amount, bool $inverse = false): float
    {
        if ($inverse) {
            return $this->amount > 0 ? $amount / $this->amount : 0;
        }

        return $amount * $this->amount;
    }

    /**
     * Obtener información completa del tipo de cambio.
     *
     * @return string
     */
    public function getFullInfo(): string
    {
        $currency = $this->getCurrencyCode();
        $value = $this->getFormattedValue();
        $updated = $this->updated_at->format('d/m/Y H:i');

        return sprintf(
            '%s: %s (actualizado: %s)',
            $currency,
            $value,
            $updated
        );
    }

    /**
     * Verificar si ha sido actualizado recientemente.
     *
     * @param int $days Días para considerar reciente
     * @return bool
     */
    public function isRecentlyUpdated(int $days = 7): bool
    {
        return $this->updated_at >= now()->subDays($days);
    }

    /**
     * Obtener el tiempo transcurrido desde la última actualización.
     *
     * @return string
     */
    public function getLastUpdateTime(): string
    {
        return $this->updated_at->diffForHumans();
    }

    /**
     * Validar que el valor sea correcto.
     *
     * @param float $value Valor a validar
     * @return bool
     */
    public static function isValidValue(float $value): bool
    {
        return $value > 0 && $value <= 999999.9999;
    }

    /**
     * Mutator para validar el valor antes de guardar.
     *
     * @param float $value
     * @return void
     */
    public function setAmountAttribute(float $value): void
    {
        if (!self::isValidValue($value)) {
            throw new \InvalidArgumentException('El valor del tipo de cambio debe estar entre 0.0001 y 999999.9999');
        }

        $this->attributes['amount'] = $value;
    }

    /**
     * Accessor para obtener información resumida.
     *
     * @return string
     */
    public function getSummaryAttribute(): string
    {
        return sprintf(
            '%s [%s]',
            $this->getCurrencyCode(),
            $this->getFormattedValue()
        );
    }
}

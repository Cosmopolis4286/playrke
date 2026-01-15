<?php

namespace App\Models\Pivots\Roles\Seller;

use App\Models\Currency;
use App\Models\Roles\Seller;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Modelo para la gestión de relaciones vendedor-país-moneda.
 *
 * Representa las asociaciones entre vendedores, países y monedas,
 * incluyendo niveles de operación y estados de las relaciones.
 */
class SellerCurrency extends Pivot
{
    use SnowflakeId;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'sellers_currencies';

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'seller',           // ID del vendedor
        'currency_id',      // ID de la moneda
        'level',            // Nivel de operación
        'status',           // Estado de la relación (activa/inactiva)
    ];

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'level' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Relación con la moneda.
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relación con el vendedor.
     *
     * @return BelongsTo
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller');
    }

    /**
     * Scope para filtrar por vendedor.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sellerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySeller($query, string $sellerId)
    {
        return $query->where('seller', $sellerId);
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
     * Scope para filtrar por estado.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, bool $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para obtener relaciones activas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope para obtener relaciones inactivas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }

    /**
     * Scope para filtrar por nivel de operación.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $level
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope para ordenar por nivel.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction Dirección del ordenamiento (asc|desc)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByLevel($query, string $direction = 'desc')
    {
        return $query->orderBy('level', $direction);
    }

    /**
     * Verificar si la relación está activa.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === true;
    }

    /**
     * Verificar si la relación está inactiva.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status === false;
    }

    /**
     * Activar la relación.
     *
     * @return bool
     */
    public function activate(): bool
    {
        return $this->update(['status' => true]);
    }

    /**
     * Desactivar la relación.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => false]);
    }

    /**
     * Obtener información del estado de la relación.
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return $this->isActive() ? 'Activa' : 'Inactiva';
    }

    /**
     * Obtener el nombre del país asociado.
     *
     * @return string
     */
    public function getCountryName(): string
    {
        return $this->country->name ?? 'Sin país';
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
     * Obtener el nombre del vendedor asociado.
     *
     * @return string
     */
    public function getSellerName(): string
    {
        return $this->seller->name ?? 'Sin vendedor';
    }

    /**
     * Verificar si el nivel está en un rango válido.
     *
     * @return bool
     */
    public function hasValidLevel(): bool
    {
        return $this->level >= 1 && $this->level <= 10;
    }

    /**
     * Obtener descripción del nivel de operación.
     *
     * @return string
     */
    public function getLevelDescription(): string
    {
        return match (true) {
            $this->level >= 1 && $this->level <= 3 => 'Básico',
            $this->level >= 4 && $this->level <= 6 => 'Intermedio',
            $this->level >= 7 && $this->level <= 8 => 'Avanzado',
            $this->level >= 9 && $this->level <= 10 => 'Experto',
            default => 'Sin nivel'
        };
    }

    /**
     * Obtener información completa de la relación.
     *
     * @return string
     */
    public function getFullInfo(): string
    {
        $seller = $this->getSellerName();
        $country = $this->getCountryName();
        $currency = $this->getCurrencyCode();
        $level = $this->getLevelDescription();
        $status = $this->getStatusLabel();

        return sprintf(
            '%s - %s/%s (Nivel: %s) [%s]',
            $seller,
            $country,
            $currency,
            $level,
            $status
        );
    }

    /**
     * Verificar si el nivel es válido.
     *
     * @param int $level Nivel a validar
     * @return bool
     */
    public static function isValidLevel(int $level): bool
    {
        return $level >= 1 && $level <= 10;
    }

    /**
     * Obtener relación por vendedor y moneda (método heredado).
     *
     * @param string $sellerId ID del vendedor
     * @param string $currencyId ID de la moneda
     * @param array $fields Campos a seleccionar
     * @return SellerCurrency|null
     */
    public static function getById(string $sellerId, string $currencyId, array $fields = ['*']): ?SellerCurrency
    {
        return self::select($fields)
            ->where('seller', $sellerId)
            ->where('currency_id', $currencyId)
            ->first();
    }

    /**
     * Obtener valor específico por vendedor y moneda (método heredado).
     *
     * @param string $sellerId ID del vendedor
     * @param string $currencyId ID de la moneda
     * @param string $field Campo a obtener
     * @return mixed
     */
    public static function getValue(string $sellerId, string $currencyId, string $field): mixed
    {
        return self::where('seller', $sellerId)
            ->where('currency_id', $currencyId)
            ->value($field);
    }

    /**
     * Obtener conteo de monedas por vendedor (método heredado).
     *
     * @param string $sellerId ID del vendedor
     * @return int
     */
    public static function getCountCurrency(string $sellerId): int
    {
        return self::select('currency_id')
            ->where('seller', $sellerId)
            ->whereNotNull('currency_id')
            ->distinct()
            ->count();
    }

    /**
     * Obtener monedas disponibles para un vendedor (método heredado).
     *
     * @param string $sellerId ID del vendedor
     * @return array
     */
    public static function getAvailableCurrencies(string $sellerId): array
    {
        return self::select('currency_id')
            ->where('seller', $sellerId)
            ->where('status', true)
            ->distinct()
            ->pluck('currency_id')
            ->toArray();
    }

    /**
     * Obtener conteo total de relaciones por vendedor (método heredado).
     *
     * @param string $sellerId ID del vendedor
     * @return int
     */
    public static function getCountCountryCurrency(string $sellerId): int
    {
        return self::where('seller', $sellerId)->count();
    }

    /**
     * Mutator para validar el nivel antes de guardar.
     *
     * @param int $value
     * @return void
     */
    public function setLevelAttribute(int $value): void
    {
        if (!self::isValidLevel($value)) {
            throw new \InvalidArgumentException('El nivel debe estar entre 1 y 10');
        }

        $this->attributes['level'] = $value;
    }

    /**
     * Accessor para obtener información resumida.
     *
     * @return string
     */
    public function getSummaryAttribute(): string
    {
        return sprintf(
            '%s/%s [Nivel %d - %s]',
            $this->getCountryName(),
            $this->getCurrencyCode(),
            $this->level,
            $this->isActive() ? 'ACTIVA' : 'INACTIVA'
        );
    }

    /**
     * Boot del modelo para eventos.
     */
    protected static function boot()
    {
        parent::boot();

        // Validar nivel antes de crear
        static::creating(function ($relation) {
            if (!self::isValidLevel($relation->level)) {
                throw new \InvalidArgumentException('El nivel debe estar entre 1 y 10');
            }
        });

        // Validar nivel antes de actualizar
        static::updating(function ($relation) {
            if ($relation->isDirty('level') && !self::isValidLevel($relation->level)) {
                throw new \InvalidArgumentException('El nivel debe estar entre 1 y 10');
            }
        });
    }
}

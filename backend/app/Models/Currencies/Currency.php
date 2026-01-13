<?php

namespace App\Models\Currencies;

use App\Models\Countries\Country;
use App\Models\Payments\ExchangeRate\ExchangeRate;
use App\Models\Users\Roles\Commerce\CommerceExchangeRate;
use App\Models\Users\Roles\Seller\Seller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use BradieTilley\Snowflake\Snowflake;

/**
 * Modelo Currency refactorizado
 *
 * Solo contiene:
 * - Configuración de la tabla y propiedades
 * - Relaciones Eloquent
 * - Boot methods para Snowflake
 * - Accessors y Mutators si es necesario
 * - Scopes útiles
 *
 * NOTA: Las consultas complejas se han movido a CurrencyRepository
 */
class Currency extends Model
{
    // ========================
    // Configuración de la tabla
    // ========================
    protected $table = 'currencies';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // ========================
    // Campos asignables
    // ========================
    protected $fillable = [
        'iso_code',
        'symbol',
        'name',
        'description',
        'type',
        'country_id',
        'decimal_places',
        'status',
    ];

    // ========================
    // Casting de tipos
    // ========================
    protected $casts = [
        'id' => 'string',
        'iso_code' => 'string',
        'symbol' => 'string',
        'type' => 'string',
        'country_id' => 'string',
        'decimal_places' => 'integer',
        'status' => 'boolean',
    ];

    // ========================
    // Boot method para Snowflake
    // ========================
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Currency $currency) {
            if (!$currency->id) {
                $currency->id = (new Snowflake())->id();
            }
        });
    }

    // ========================
    // Relaciones
    // ========================

    /**
     * Relación: una moneda pertenece a un país
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Relación: una moneda tiene una tasa de cambio actual
     */
    public function exchangeRate(): HasOne
    {
        return $this->hasOne(ExchangeRate::class, 'currency_id');
    }

    /**
     * Relación: una moneda tiene muchas tasas de cambio por comercio
     */
    public function commerceExchangeRates(): HasMany
    {
        return $this->hasMany(CommerceExchangeRate::class, 'currency_id');
    }

    /**
     * Relación: una moneda tiene muchos vendedores que la aceptan
     */
    public function sellers(): HasMany
    {
        return $this->hasMany(Seller::class, 'currency_id');
    }

    // ========================
    // Accessors
    // ========================

    /**
     * Verificar si la moneda está activa
     */
    public function getIsActiveStatusAttribute(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Verificar si es moneda FIAT
     */
    public function getIsFiatAttribute(): bool
    {
        return $this->type === 'FIAT';
    }

    /**
     * Verificar si es criptomoneda
     */
    public function getIsCryptoAttribute(): bool
    {
        return $this->type === 'CRYPTO';
    }

    /**
     * Verificar si es token
     */
    public function getIsTokenAttribute(): bool
    {
        return $this->type === 'TOKEN';
    }

    /**
     * Obtener nombre para mostrar con símbolo
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->symbol
            ? "{$this->name} ({$this->symbol})"
            : $this->name;
    }

    /**
     * Obtener nombre completo con código ISO
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->name} - {$this->iso_code}";
    }

    /**
     * Formatear valor con decimales correctos
     */
    public function formatAmount(float $amount): string
    {
        return number_format($amount, $this->decimal_places, '.', ',');
    }

    // ========================
    // Scopes útiles
    // ========================

    /**
     * Scope para monedas activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope para monedas inactivas
     */
    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }

    /**
     * Scope para monedas FIAT
     */
    public function scopeFiat($query)
    {
        return $query->where('type', 'FIAT');
    }

    /**
     * Scope para criptomonedas
     */
    public function scopeCrypto($query)
    {
        return $query->where('type', 'CRYPTO');
    }

    /**
     * Scope para tokens
     */
    public function scopeTokens($query)
    {
        return $query->where('type', 'TOKEN');
    }

    /**
     * Scope para ordenar por nombre
     */
    public function scopeOrderedByName($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Scope para buscar por código ISO
     */
    public function scopeByIsoCode($query, string $isoCode)
    {
        return $query->where('iso_code', strtoupper($isoCode));
    }

    /**
     * Scope para buscar por tipo
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', strtoupper($type));
    }

    /**
     * Scope para monedas con país asociado
     */
    public function scopeWithCountry($query)
    {
        return $query->whereNotNull('country_id');
    }

    /**
     * Scope para monedas sin país asociado
     */
    public function scopeWithoutCountry($query)
    {
        return $query->whereNull('country_id');
    }

    /**
     * Scope para monedas con decimales específicos
     */
    public function scopeWithDecimals($query, int $decimals)
    {
        return $query->where('decimal_places', $decimals);
    }

    /**
     * Scope para monedas con tasa de cambio
     */
    public function scopeWithExchangeRate($query)
    {
        return $query->whereHas('exchangeRate');
    }
}

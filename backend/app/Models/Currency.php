<?php

namespace App\Models;

use App\Models\Roles\Commerce\CommerceExchangeRate;
use App\Models\Roles\Seller;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
    use HasFactory, SnowflakeId;
    public $incrementing = false;

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
        'decimal_separator' => 'string',
        'thousands_separator' => 'string',
        'status' => 'string',
    ];

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
        'decimal_separator',
        'thousands_separator',
        'status',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    // ========================
    // Configuración de la tabla
    // ========================
    protected $table = 'currencies';

    /**
     * Símbolos de moneda por ID, cacheados 5 min (igual TTL que
     * CurrencyExchangeRateService — los símbolos cambian aún menos que la
     * tasa de cambio). Evita resolver `currencies` por request en pantallas
     * de alto tráfico/polling (p. ej. lobby y sala de espera de Trivia).
     *
     * @param array<int, string> $ids
     *
     * @return array<string, string> id => symbol
     */
    public static function symbolsByIds(array $ids) : array
    {
        $ids = array_values(array_unique(array_map('strval', $ids)));
        if (empty($ids)) {
            return [];
        }

        sort($ids);
        $cacheKey = 'currency_symbols_by_ids:' . md5(implode(',', $ids));

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function() use ($ids) {
            return static::whereIn('id', $ids)->pluck('symbol', 'id')->all();
        });
    }

    /**
     * ID (Snowflake) de la moneda USD, cacheado por request.
     *
     * Con IDs Snowflake el ID de USD nunca es el entero 1 — varios puntos
     * del código asumían `currency_id === 1` como atajo para "es USD", lo
     * cual siempre era falso y ocultaba silenciosamente el problema real
     * (ver ExchangeRateException). Usar este helper en su lugar.
     */
    public static function usdId() : ?string
    {
        return \Illuminate\Support\Facades\Cache::remember('currency_usd_id', 60, function() {
            return static::where('iso_code', 'USD')->value('id');
        });
    }

    /**
     * Relación: una moneda tiene muchas tasas de cambio por comercio
     */
    public function commerceExchangeRates() : HasMany
    {
        return $this->hasMany(CommerceExchangeRate::class, 'currency_id');
    }

    // ========================
    // Relaciones
    // ========================

    /**
     * Relación: una moneda pertenece a un país
     */
    public function country() : BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Relación: una moneda tiene una tasa de cambio actual
     */
    public function exchangeRate() : HasOne
    {
        return $this->hasOne(ExchangeRate::class, 'currency_id');
    }

    /**
     * Formatear valor con decimales correctos
     */
    public function formatAmount(float $amount, ?int $decimals = null) : string
    {
        return number_format(
            $amount,
            $decimals ?? $this->decimal_places ?? 2,
            $this->decimal_separator ?? ',',
            $this->thousands_separator ?? '.',
        );
    }

    /**
     * Obtener nombre para mostrar con símbolo
     */
    public function getDisplayNameAttribute() : string
    {
        return $this->symbol
            ? "{$this->name} ({$this->symbol})"
            : $this->name;
    }

    /**
     * Obtener nombre completo con código ISO
     */
    public function getFullNameAttribute() : string
    {
        return "{$this->name} - {$this->iso_code}";
    }

    // ========================
    // Accessors
    // ========================

    /**
     * Verificar si la moneda está activa
     */
    public function getIsActiveStatusAttribute() : bool
    {
        return $this->status === true;
    }

    /**
     * Verificar si es criptomoneda
     */
    public function getIsCryptoAttribute() : bool
    {
        return $this->type === 'CRYPTO';
    }

    /**
     * Verificar si es moneda FIAT
     */
    public function getIsFiatAttribute() : bool
    {
        return $this->type === 'FIAT';
    }

    /**
     * Verificar si es token
     */
    public function getIsTokenAttribute() : bool
    {
        return $this->type === 'TOKEN';
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
     * Scope para criptomonedas
     */
    public function scopeCrypto($query)
    {
        return $query->where('type', 'CRYPTO');
    }

    /**
     * Scope para monedas FIAT
     */
    public function scopeFiat($query)
    {
        return $query->where('type', 'FIAT');
    }

    /**
     * Scope para monedas inactivas
     */
    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }

    /**
     * Scope para ordenar por nombre
     */
    public function scopeOrderedByName($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Scope para tokens
     */
    public function scopeTokens($query)
    {
        return $query->where('type', 'TOKEN');
    }

    /**
     * Scope para monedas con país asociado
     */
    public function scopeWithCountry($query)
    {
        return $query->whereNotNull('country_id');
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

    /**
     * Scope para monedas sin país asociado
     */
    public function scopeWithoutCountry($query)
    {
        return $query->whereNull('country_id');
    }

    /**
     * Relación: una moneda tiene muchos vendedores que la aceptan
     */
    public function sellers() : HasMany
    {
        return $this->hasMany(Seller::class, 'currency_id');
    }
}

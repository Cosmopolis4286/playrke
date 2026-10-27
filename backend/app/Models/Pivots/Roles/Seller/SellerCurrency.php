<?php

namespace App\Models\Pivots\Roles\Seller;

use App\Models\Currency;
use App\Models\Pivots\BasePivot;
use App\Models\Roles\Seller;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Modelo Pivot para las monedas de los vendedores.
 *
 * Este pivot:
 * - No posee clave primaria propia
 * - Se identifica por una clave compuesta
 * - Es auditable mediante identidad serializada
 */
class SellerCurrency extends BasePivot
{
    public $incrementing = false;

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'seller_id' => 'string',
        'currency_id' => 'string',
        'level' => 'integer',
    ];

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'seller_id',           // ID del vendedor
        'currency_id',      // ID de la moneda
        'level',            // Nivel de operación
        'status',           // Estado de la relación (activa/inactiva)
    ];

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'sellers_currencies';

    /**
     * Obtener monedas disponibles para un vendedor (método heredado).
     *
     * @param string $sellerId ID del vendedor
     */
    public static function getAvailableCurrencies(string $sellerId) : array
    {
        return self::select('currency_id')
            ->where('seller_id', $sellerId)
            ->where('status', true)
            ->distinct()
            ->pluck('currency_id')
            ->toArray();
    }

    /**
     * Obtener relación por vendedor y moneda (método heredado).
     *
     * @param string $sellerId   ID del vendedor
     * @param string $currencyId ID de la moneda
     * @param array  $fields     Campos a seleccionar
     */
    public static function getById(string $sellerId, string $currencyId, array $fields = ['*']) : ?self
    {
        return self::select($fields)
            ->where('seller_id', $sellerId)
            ->where('currency_id', $currencyId)
            ->first();
    }

    /**
     * Obtener conteo de monedas por vendedor (método heredado).
     *
     * @param string $sellerId ID del vendedor
     */
    public static function getCountCurrency(string $sellerId) : int
    {
        return self::select('currency_id')
            ->where('seller_id', $sellerId)
            ->whereNotNull('currency_id')
            ->distinct()
            ->count();
    }

    /**
     * Obtener valor específico por vendedor y moneda (método heredado).
     *
     * @param string $sellerId   ID del vendedor
     * @param string $currencyId ID de la moneda
     * @param string $field      Campo a obtener
     */
    public static function getValue(string $sellerId, string $currencyId, string $field) : mixed
    {
        return self::where('seller_id', $sellerId)
            ->where('currency_id', $currencyId)
            ->value($field);
    }

    /**
     * Verificar si el nivel es válido.
     *
     * @param int $level Nivel a validar
     */
    public static function isValidLevel(int $level) : bool
    {
        return $level >= 1 && $level <= 10;
    }

    /**
     * Activar la relación.
     */
    public function activate() : bool
    {
        return $this->update(['status' => true]);
    }

    /**
     * Relación con la moneda.
     */
    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Desactivar la relación.
     */
    public function deactivate() : bool
    {
        return $this->update(['status' => false]);
    }

    /**
     * Obtener el código de la moneda asociada.
     */
    public function getCurrencyCode() : string
    {
        return $this->currency->code ?? 'Sin moneda';
    }

    /**
     * Obtener información completa de la relación.
     */
    public function getFullInfo() : string
    {
        $seller = $this->getSellerName();

        $currency = $this->getCurrencyCode();
        $level = $this->getLevelDescription();
        $status = $this->getStatusLabel();

        return sprintf(
            '%s - %s/%s (Nivel: %s) [%s]',
            $seller,
            $currency,
            $level,
            $status,
        );
    }

    /**
     * Obtener descripción del nivel de operación.
     */
    public function getLevelDescription() : string
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
     * Obtener el nombre del vendedor asociado.
     */
    public function getSellerName() : string
    {
        return $this->seller->name ?? 'Sin vendedor';
    }

    /**
     * Obtener información del estado de la relación.
     */
    public function getStatusLabel() : string
    {
        return $this->isActive() ? 'Activa' : 'Inactiva';
    }

    /**
     * Accessor para obtener información resumida.
     */
    public function getSummaryAttribute() : string
    {
        return sprintf(
            '%s/%s [Nivel %d - %s]',
            $this->seller->country->name ?? 'Sin país',
            $this->getCurrencyCode(),
            $this->level,
            $this->isActive() ? 'ACTIVA' : 'INACTIVA',
        );
    }

    /**
     * Verificar si el nivel está en un rango válido.
     */
    public function hasValidLevel() : bool
    {
        return $this->level >= 1 && $this->level <= 10;
    }

    /**
     * Verificar si la relación está activa.
     */
    public function isActive() : bool
    {
        return $this->status === true;
    }

    /**
     * Verificar si la relación está inactiva.
     */
    public function isInactive() : bool
    {
        return $this->status === false;
    }

    /**
     * Scope para obtener relaciones activas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
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
     * Scope para filtrar por nivel de operación.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope para filtrar por vendedor.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySeller($query, string $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Scope para filtrar por estado.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, bool $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para obtener relaciones inactivas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }

    /**
     * Scope para ordenar por nivel.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $direction Dirección del ordenamiento (asc|desc)
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByLevel($query, string $direction = 'desc')
    {
        return $query->orderBy('level', $direction);
    }

    /**
     * Relación con el vendedor.
     */
    public function seller() : BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Mutator para validar el nivel antes de guardar.
     */
    public function setLevelAttribute(int $value) : void
    {
        if (! self::isValidLevel($value)) {
            throw new InvalidArgumentException('El nivel debe estar entre 1 y 10');
        }

        $this->attributes['level'] = $value;
    }
}

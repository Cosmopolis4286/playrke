<?php

namespace App\Models\Roles;

use App\Models\BaseModel;
use App\Models\Currency;
use App\Models\Country;
use App\Models\Pivots\Roles\Seller\SellerCurrency;
use App\Models\Roles\Commerce;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para la gestión de vendedores.
 *
 * Representa los vendedores del sistema con información de país, moneda,
 * comisiones, comercio asociado y relaciones con usuarios.
 */
class Seller extends BaseModel
{
    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'sellers';

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',           // ID del vendedor (generado con Snowflake)
        'name',         // Nombre del vendedor
        'phone',        // Teléfono del vendedor
        'commission',   // Comisión del vendedor
        'user_id',         // ID del usuario asociado
        'country_id',      // ID del país
        'manager_user_id', // ID del usuario manager al que representa
        'status',       // Estado del vendedor (activo/inactivo)
    ];

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'commission' => 'decimal:2',
        'user_id' => 'string',
        'country_id' => 'string',
        'manager_user_id' => 'string',
    ];

    /**
     * Relación con el país del vendedor.
     *
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Relación con la moneda del vendedor.
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relación con el comercio del vendedor.
     *
     * @return BelongsTo
     */
    public function commerce(): BelongsTo
    {
        return $this->belongsTo(Commerce::class, 'manager_user_id');
    }

    /**
     * Relación con el usuario del vendedor.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación del vendedor con las monedas y países.
     *
     * @return HasMany
     */
    public function countriesCurrencies(): HasMany
    {
        return $this->hasMany(SellerCurrency::class, 'seller', 'id');
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
     * Scope para obtener vendedores activos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope para obtener vendedores inactivos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }

    /**
     * Scope para filtrar por país.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $countryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCountry($query, string $countryId)
    {
        return $query->where('country_id', $countryId);
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
     * Scope para filtrar por comercio.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $commerceId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCommerce($query, string $commerceId)
    {
        return $query->where('manager_user_id', $commerceId);
    }

    /**
     * Scope para buscar por nombre.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'LIKE', "%{$name}%");
    }

    /**
     * Scope para filtrar por rango de comisión.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $min
     * @param float $max
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCommissionRange($query, float $min, float $max)
    {
        return $query->whereBetween('commission', [$min, $max]);
    }

    /**
     * Scope para ordenar por comisión.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction Dirección del ordenamiento (asc|desc)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCommission($query, string $direction = 'desc')
    {
        return $query->orderBy('commission', $direction);
    }

    /**
     * Verificar si el vendedor está activo.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Verificar si el vendedor está inactivo.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status === 'INACTIVE';
    }

    /**
     * Activar el vendedor.
     *
     * @return bool
     */
    public function activate(): bool
    {
        return $this->update(['status' => true]);
    }

    /**
     * Desactivar el vendedor.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'INACTIVE']);
    }

    /**
     * Obtener información del estado del vendedor.
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return $this->isActive() ? 'Activo' : 'Inactivo';
    }

    /**
     * Verificar si el vendedor tiene teléfono registrado.
     *
     * @return bool
     */
    public function hasPhone(): bool
    {
        return !empty($this->phone);
    }

    /**
     * Verificar si el vendedor tiene comercio asignado.
     *
     * @return bool
     */
    public function hasCommerce(): bool
    {
        return !empty($this->manager_user_id);
    }

    /**
     * Obtener la comisión formateada.
     *
     * @return string
     */
    public function getFormattedCommission(): string
    {
        return number_format($this->commission, 2) . '%';
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
     * Obtener el nombre del comercio asociado.
     *
     * @return string
     */
    public function getCommerceName(): string
    {
        return $this->commerce->name ?? 'Sin comercio';
    }

    /**
     * Verificar si el vendedor está siendo utilizado en transacciones.
     *
     * @return bool
     */
    public function isInUse(): bool
    {
        // Verificar si tiene registros en tablas relacionadas
        return $this->countriesCurrencies()->exists();
    }

    /**
     * Verificar si la comisión está en un rango válido.
     *
     * @return bool
     */
    public function hasValidCommission(): bool
    {
        return $this->commission >= 0 && $this->commission <= 100;
    }

    /**
     * Obtener información completa del vendedor.
     *
     * @return string
     */
    public function getFullInfo(): string
    {
        $status = $this->getStatusLabel();
        $commission = $this->getFormattedCommission();
        $country = $this->getCountryName();
        $currency = $this->getCurrencyCode();

        return sprintf(
            '%s (%s) - %s - %s/%s',
            $this->name,
            $status,
            $commission,
            $country,
            $currency
        );
    }

    /**
     * Determinar si se puede eliminar el vendedor.
     *
     * @return bool
     */
    public function canBeDeleted(): bool
    {
        return !$this->isInUse();
    }

    /**
     * Validar que la comisión esté en rango válido.
     *
     * @param float $commission Comisión a validar
     * @return bool
     */
    public static function isValidCommission(float $commission): bool
    {
        return $commission >= 0 && $commission <= 100;
    }

    /**
     * Obtener moneda por ID de vendedor (método heredado).
     *
     * @param string $sellerId ID del vendedor
     * @return string|null
     */
    public static function getCurrencyByIdId(string $sellerId): ?string
    {
        return self::where('id', $sellerId)->value('currency');
    }

    /**
     * Obtener vendedor por ID con campos específicos (método heredado).
     *
     * @param string $id ID del vendedor
     * @param array $fields Campos a seleccionar
     * @return Seller|null
     */
    public static function getById(string $id, array $fields = ['*']): ?Seller
    {
        return self::select($fields)->where('id', $id)->first();
    }

    /**
     * Obtener vendedor por ID de usuario (método heredado).
     *
     * @param string $userId ID del usuario
     * @param array $fields Campos a seleccionar
     * @return Seller|null
     */
    public static function getByUserId(string $userId, array $fields = ['*']): ?Seller
    {
        return self::select($fields)->where('user_id', $userId)->first();
    }

    /**
     * Mutator para validar la comisión antes de guardar.
     *
     * @param float $value
     * @return void
     */
    public function setCommissionAttribute(float $value): void
    {
        if (!self::isValidCommission($value)) {
            throw new \InvalidArgumentException('La comisión debe estar entre 0 y 100');
        }

        $this->attributes['commission'] = $value;
    }

    /**
     * Mutator para limpiar el nombre antes de guardar.
     *
     * @param string $value
     * @return void
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = trim($value);
    }

    /**
     * Mutator para limpiar el teléfono antes de guardar.
     *
     * @param string|null $value
     * @return void
     */
    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = $value ? trim($value) : null;
    }

    /**
     * Accessor para obtener información resumida.
     *
     * @return string
     */
    public function getSummaryAttribute(): string
    {
        return sprintf(
            '%s [%s - %s]',
            $this->name,
            $this->getFormattedCommission(),
            $this->isActive() ? 'ACTIVE' : 'INACTIVE'
        );
    }

    /**
     * Boot del modelo para eventos.
     */
    protected static function boot()
    {
        parent::boot();

        // Antes de eliminar, verificar que no esté en uso
        static::deleting(function ($seller) {
            if ($seller->isInUse()) {
                throw new \Exception('No se puede eliminar un vendedor que está siendo utilizado.');
            }
        });
    }
}

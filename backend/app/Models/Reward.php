<?php

namespace App\Models;

use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para recompensas del sistema
 *
 * Representa recompensas que pueden ser productos o servicios,
 * individuales o en kit, creadas por usuarios del sistema.
 *
 * @package App\Models
 * @author Sistema de Gestión de Recompensas
 * @version 1.0
 *
 * @property string $id ID único de la recompensa
 * @property string $code Código único de la recompensa
 * @property string $reward_type Tipo de recompensa
 * @property string $name Nombre de la recompensa
 * @property string $kit Indica si es un kit
 * @property int $num_items Número de ítems en el kit
 * @property float $price Precio de la recompensa
 * @property string|null $description Descripción de la recompensa
 * @property string $user_id ID del usuario creador
 * @property string $status Estado de la recompensa
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 *
 * @property-read User $user Usuario creador de la recompensa
 */
class Reward extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'rewards';

    /**
     * Tipos de recompensas disponibles
     */
    public const REWARD_TYPES = [
        'PRODUCT' => 'Producto',
        'SERVICE' => 'Servicio',
    ];

    /**
     * Estados disponibles para recompensas
     */
    public const STATUSES = [
        'ACTIVE' => 'Activo',
        'INACTIVE' => 'Inactivo',
    ];

    /**
     * Opciones para kit
     */
    public const KIT_OPTIONS = [
        'Y' => 'Sí',
        'N' => 'No',
    ];

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'code',
        'reward_type',
        'name',
        'kit',
        'num_items',
        'price',
        'description',
        'user_id',
        'status',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'price' => 'decimal:2',
        'num_items' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Campos adicionales que se incluyen en las respuestas JSON
     */
    protected $appends = [
        'reward_type_description',
        'kit_description',
        'status_description',
    ];

    /**
     * Relación con el usuario creador de la recompensa
     *
     * @return BelongsTo<User, Reward>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Obtiene la descripción del tipo de recompensa
     *
     * @return string
     */
    public function getRewardTypeDescriptionAttribute(): string
    {
        return self::REWARD_TYPES[$this->reward_type] ?? $this->reward_type;
    }

    /**
     * Obtiene la descripción del kit
     *
     * @return string
     */
    public function getKitDescriptionAttribute(): string
    {
        return self::KIT_OPTIONS[$this->kit] ?? $this->kit;
    }

    /**
     * Obtiene la descripción del estado
     *
     * @return string
     */
    public function getStatusDescriptionAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Verifica si la recompensa está activa
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Verifica si la recompensa está inactiva
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status === 'INACTIVE';
    }

    /**
     * Verifica si la recompensa es un producto
     *
     * @return bool
     */
    public function isProduct(): bool
    {
        return $this->reward_type === 'PRODUCT';
    }

    /**
     * Verifica si la recompensa es un servicio
     *
     * @return bool
     */
    public function isService(): bool
    {
        return $this->reward_type === 'SERVICE';
    }

    /**
     * Verifica si la recompensa es un kit
     *
     * @return bool
     */
    public function isKit(): bool
    {
        return $this->kit === 'Y';
    }

    /**
     * Verifica si la recompensa es individual
     *
     * @return bool
     */
    public function isIndividual(): bool
    {
        return $this->kit === 'N';
    }

    /**
     * Obtiene el precio formateado
     *
     * @return string
     */
    public function getFormattedPrice(): string
    {
        return number_format($this->price, 2);
    }

    /**
     * Scope para obtener solo recompensas activas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope para obtener solo recompensas inactivas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'INACTIVE');
    }

    /**
     * Scope para obtener recompensas por tipo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('reward_type', $type);
    }

    /**
     * Scope para obtener solo productos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProducts($query)
    {
        return $query->where('reward_type', 'PRODUCT');
    }

    /**
     * Scope para obtener solo servicios
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeServices($query)
    {
        return $query->where('reward_type', 'SERVICE');
    }

    /**
     * Scope para obtener solo kits
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeKits($query)
    {
        return $query->where('kit', 'Y');
    }

    /**
     * Scope para obtener solo recompensas individuales
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIndividual($query)
    {
        return $query->where('kit', 'N');
    }

    /**
     * Scope para buscar por código
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $code
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope para obtener recompensas de un usuario
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para obtener recompensas en un rango de precios
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minPrice
     * @param float $maxPrice
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePriceRange($query, float $minPrice, float $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }
}

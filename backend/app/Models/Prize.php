<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para recompensas del sistema
 *
 * Representa recompensas que pueden ser productos o servicios,
 * individuales o en kit, creadas por usuarios del sistema.
 *
 * @author Sistema de Gestión de Recompensas
 *
 * @version 1.0
 *
 * @property string         $id          ID único de la recompensa
 * @property string         $code        Código único de la recompensa
 * @property string         $reward_type Tipo de recompensa
 * @property string         $name        Nombre de la recompensa
 * @property string         $kit         Indica si es un kit
 * @property int            $num_items   Número de ítems en el kit
 * @property float          $price       Precio de la recompensa
 * @property string|null    $description Descripción de la recompensa
 * @property string         $user_id     ID del usuario creador
 * @property string         $status      Estado de la recompensa
 * @property \Carbon\Carbon $created_at  Fecha de creación
 * @property \Carbon\Carbon $updated_at  Fecha de actualización
 * @property User           $user        Usuario creador de la recompensa
 */
class Prize extends BaseModel
{
    /**
     * Opciones para kit
     */
    public const KIT_OPTIONS = [
        'Y' => 'Sí',
        'N' => 'No',
    ];

    /**
     * Tipos de recompensas disponibles
     */
    public const REWARD_TYPES = [
        'PRODUCT' => 'Producto',
        'SERVICE' => 'Servicio',
        'MIXED' => 'Mixta',
    ];

    /**
     * Estados disponibles para recompensas
     */
    public const STATUSES = [
        'ACTIVE' => 'Activo',
        'INACTIVE' => 'Inactivo',
    ];

    /**
     * Campos adicionales que se incluyen en las respuestas JSON
     */
    protected $appends = [
        'prize_type_description',
        'kit_description',
        'status_badge',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'price' => 'decimal:2',
        'num_items' => 'integer',
        'kit_items' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'code',
        'slug',
        'reward_type',
        'name',
        'kit',
        'num_items',
        'kit_items',
        'price',
        'description',
        'user_id',
        'status',
    ];
    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'prizes';

    /**
     * Accessor: precio con símbolo $ para tablas (formatted_price)
     */
    public function getFormattedPriceAttribute() : string
    {
        return '$' . number_format((float) $this->price, 2, '.', ',');
    }

    /**
     * Obtiene la descripción del kit
     */
    public function getKitDescriptionAttribute() : string
    {
        return self::KIT_OPTIONS[$this->kit] ?? $this->kit;
    }

    /**
     * Obtiene la descripción del tipo de recompensa
     */
    public function getPrizeTypeDescriptionAttribute() : string
    {
        return self::REWARD_TYPES[$this->reward_type] ?? $this->reward_type;
    }

    /**
     * Accessor para el status con badge HTML
     */
    public function getStatusBadgeAttribute() : string
    {
        return match ($this->status) {
            'ACTIVE' => '<span class="badge badge-success">Activo</span>',
            'INACTIVE' => '<span class="badge badge-secondary">Inactivo</span>',
            default => '<span class="badge badge-warning">Desconocido</span>'
        };
    }

    /**
     * Obtiene la descripción del estado
     */
    public function getStatusDescriptionAttribute() : string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Verifica si la recompensa está activa
     */
    public function isActive() : bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Verifica si la recompensa está inactiva
     */
    public function isInactive() : bool
    {
        return $this->status === 'INACTIVE';
    }

    /**
     * Verifica si la recompensa es individual
     */
    public function isIndividual() : bool
    {
        return $this->kit === 'N';
    }

    /**
     * Verifica si la recompensa es un kit
     */
    public function isKit() : bool
    {
        return $this->kit === 'Y';
    }

    /**
     * Verifica si la recompensa es un producto
     */
    public function isProduct() : bool
    {
        return $this->reward_type === 'PRODUCT';
    }

    /**
     * Verifica si la recompensa es un servicio
     */
    public function isService() : bool
    {
        return $this->reward_type === 'SERVICE';
    }

    /**
     * Scope para obtener solo recompensas activas
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
     * Scope para buscar por código
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope para obtener recompensas por tipo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('reward_type', $type);
    }

    /**
     * Scope para obtener recompensas de un usuario
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para obtener solo recompensas inactivas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'INACTIVE');
    }

    /**
     * Scope para obtener solo recompensas individuales
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIndividual($query)
    {
        return $query->where('kit', 'N');
    }

    /**
     * Scope para obtener solo kits
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeKits($query)
    {
        return $query->where('kit', 'Y');
    }

    /**
     * Scope para obtener recompensas en un rango de precios
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePriceRange($query, float $minPrice, float $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    /**
     * Scope para obtener solo productos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
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
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeServices($query)
    {
        return $query->where('reward_type', 'SERVICE');
    }

    /**
     * Relación con el usuario creador de la recompensa
     *
     * @return BelongsTo<User, Prize>
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models\Games\Bingo\Creators;

use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para los creadores de salas de bingo
 *
 * Representa la entidad que gestiona los creadores de salas de bingo.
 * Incluye información sobre el usuario creador, tipo de creador, comisiones y configuraciones.
 *
 * @package App\Models\Bingo\Creators
 * @author Sistema PlayRKE
 * @version 1.0
 *
 * @property string $id ID único del creador de sala
 * @property string $room ID de la sala de bingo
 * @property string $creator_user_id ID del usuario creador
 * @property string $creator_type Tipo de creador
 * @property string $creator_id ID del creador específico
 * @property float $creator_commn Comisión del creador
 * @property float $card_price Precio de las cartas
 * @property array $allowed_currencies Monedas permitidas
 * @property string $status Estado del creador (active, inactive)
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 *
 * @property-read BingoRoom $room Sala de bingo asociada
 * @property-read Role $creatorType Tipo de creador asociado
 */
class BingoCreator extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'bingo_creator';

    /**
     * Estados disponibles para el creador
     */
    public const STATUSES = [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
    ];

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'room',
        'creator_user_id',
        'creator_type',
        'creator_id',
        'creator_commn',
        'card_price',
        'allowed_currencies',
        'status',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'allowed_currencies' => 'array',
        'creator_commn' => 'decimal:8',
        'card_price' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con la sala de bingo
     *
     * @return BelongsTo<BingoRoom, BingoCreator>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    /**
     * Relación con el tipo de creador
     *
     * @return BelongsTo<Role, BingoCreator>
     */
    public function creatorType(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'creator_type');
    }

    /**
     * Scope para obtener solo creadores activos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope para obtener solo creadores inactivos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope para obtener creadores por tipo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('creator_type', $type);
    }

    /**
     * Scope para obtener creadores por sala
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $roomId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRoom($query, string $roomId)
    {
        return $query->where('room', $roomId);
    }

    /**
     * Scope para obtener creadores por usuario
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('creator_user_id', $userId);
    }

    /**
     * Scope para obtener creadores por rango de precios
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minPrice
     * @param float $maxPrice
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriceRange($query, float $minPrice, float $maxPrice)
    {
        return $query->whereBetween('card_price', [$minPrice, $maxPrice]);
    }

    /**
     * Scope para obtener creadores que soportan una moneda específica
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $currency
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCurrency($query, string $currency)
    {
        return $query->whereJsonContains('allowed_currencies', $currency);
    }

    /**
     * Accessor para obtener la descripción del estado
     *
     * @return string
     */
    public function getStatusDescriptionAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Accessor para verificar si el creador está activo
     *
     * @return bool
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }
}

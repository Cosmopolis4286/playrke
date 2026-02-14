<?php

namespace App\Models\Games\Bingo\Creator;

use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Role;
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
 * @property string $user_id ID del usuario creador
 * @property float $creator_commn Comisión del creador
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
    protected $table = 'bingo_creators';

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
        'room_id',
        'user_id',
        'creator_commn',
        'status',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'room_id' => 'string',
        'user_id' => 'string',
        'creator_commn' => 'decimal:8',
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
        return $this->belongsTo(BingoRoom::class, 'room_id');
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
     * Scope para obtener creadores por sala
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $roomId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRoom($query, string $roomId)
    {
        return $query->where('room_id', $roomId);
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
        return $query->where('user_id', $userId);
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

<?php

namespace App\Models\Games\Common\Room;

use Illuminate\Database\Eloquent\Model;
use App\Traits\SnowflakeId;

/**
 * Modelo para tipos de salas (RoomType).
 *
 * Se usa para definir y administrar los tipos disponibles:
 * PRIVATE, PUBLIC, VIP, MIXED, etc.
 */
class RoomType extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'room_types';

    protected $primaryKey = 'id';

    public $incrementing = false;

    /**
     * Los campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',       // Código del tipo (PRIVATE|PUBLIC|VIP|MIXED)
        'label',    // Etiqueta visible
        'status',   // Estado (ACTIVE|INACTIVE)
    ];

    /**
     * Casts.
     *
     * @var array<string, string>
     */
    protected $casts = [];

    /**
     * Scope para filtrar tipos activos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Verifica si el tipo está activo.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Verifica si el tipo está inactivo.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status === 'INACTIVE';
    }
}

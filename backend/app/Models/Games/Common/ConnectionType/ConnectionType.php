<?php

namespace App\Models\Games\Common\ConnectionType;

use Illuminate\Database\Eloquent\Model;
use App\Traits\SnowflakeId;
use App\Models\Games\Common\PrizePool\GamePrizePoolType;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para tipos de conexión (ConnectionType).
 *
 * Se usa para definir y administrar los canales de conexión disponibles
 * para una sala: ONLINE, OFFLINE, MIXED.
 */
class ConnectionType extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'connection_types';

    protected $primaryKey = 'id';

    public $incrementing = false;

    /**
     * Los campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',     // Código del tipo (ONLINE|OFFLINE|MIXED)
        'label',    // Etiqueta visible
        'status',   // Estado (ACTIVE|INACTIVE)
    ];

     /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',  // Casteo del ID a string para manejar Snowflake
        'code' => 'string',  // Casteo del código a string
        'label' => 'string',  // Casteo de la etiqueta a string
        'status' => 'string',  // Casteo del estado a string
    ];

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

    /**
     * Tipos de pozo de premios asociados a este tipo de conexión.
     *
     * @return HasMany
     */
    public function gamePrizePoolTypes(): HasMany
    {
        return $this->hasMany(GamePrizePoolType::class, 'connection_type_id');
    }
}

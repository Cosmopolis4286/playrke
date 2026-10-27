<?php

namespace App\Models\Games\Common\ConnectionType;

use App\Traits\HasActiveStatusScope;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para tipos de conexión (ConnectionType).
 *
 * Se usa para definir y administrar los canales de conexión disponibles
 * para una sala: ONLINE, OFFLINE, MIXED.
 */
class ConnectionType extends Model
{
    use HasActiveStatusScope, SnowflakeId;

    public $incrementing = false;

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
     * Los campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',     // Código del tipo (ONLINE|OFFLINE|MIXED)
        'label',    // Etiqueta visible
        'status',   // Estado (ACTIVE|INACTIVE)
    ];

    protected $primaryKey = 'id';

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'connection_types';
}

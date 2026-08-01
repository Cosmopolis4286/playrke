<?php

namespace App\Models\Games\Common\Room;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasActiveStatusScope;
use App\Traits\SnowflakeId;

/**
 * Modelo para tipos de salas (RoomType).
 *
 * Se usa para definir y administrar los tipos disponibles:
 * PRIVATE, PUBLIC, VIP, DEMO, etc.
 */
class RoomType extends Model
{
    use SnowflakeId, HasActiveStatusScope;

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
        'code',     // Código del tipo (PRIVATE|PUBLIC|VIP|MIXED)
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

}

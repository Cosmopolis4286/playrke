<?php

namespace App\Models;

use App\Casts\AsActiveStatusEnum;
use App\Models\Country;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo TimeZone
 *
 * Representa una zona horaria del sistema, asociada a un país y con
 * su correspondiente valor UTC. Se apoya en SnowflakeId para
 * generar IDs únicos y no secuenciales.
 */
class TimeZone extends Model
{
    use SnowflakeId;

    /**
     * Tabla asociada.
     */
    protected $table = 'time_zones';

    /**
     * Atributos asignables.
     */
    protected $fillable = [
        'name',
        'utc',
        'country_id',
        'status',
    ];

    /**
     * Conversión de tipos.
     */
    protected $casts = [
        'id' => 'string',
        'country_id' => 'string',
        'status' => AsActiveStatusEnum::class,
    ];

    /**
     * País asociado a esta zona horaria.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}

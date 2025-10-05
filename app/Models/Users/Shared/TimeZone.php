<?php

namespace App\Models\Users\Shared;

use App\Models\Countries\Country;
use App\Traits\HasSnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para zonas horarias del sistema.
 *
 * Representa las zonas horarias disponibles en el sistema, incluyendo
 * su configuración UTC, país asociado y estado de activación para
 * su uso en la aplicación.
 */
class TimeZone extends Model
{
    use HasSnowflakeId;

    protected $table = "time_zones";

    protected $fillable = [
        'name',
        'utc',
        'country_id',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    /**
     * Relación con el país.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}

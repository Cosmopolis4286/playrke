<?php

namespace App\Models;

use App\Models\Roles\Commerce;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuración global de la plataforma — tabla singleton (siempre existe
 * exactamente 1 fila, sembrada en la migración de creación).
 *
 * Distinta de Setting (tabla `settings`, key-value genérica de parámetros
 * sueltos sin FK tipada) — PlatformConfig es para configuración
 * estructurada que necesita apuntar de forma confiable a otros registros
 * reales (ver defaultCommerce()).
 *
 * No extiende BaseModel: no necesita SoftDeletes (config interna, no un
 * recurso que se archive) ni HasHashedRouteKey (nunca se expone su id en
 * rutas públicas).
 */
class PlatformConfig extends Model
{
    use SnowflakeId;

    protected $table = 'platform_config';

    protected $fillable = [
        'default_commerce_id',
    ];

    protected $casts = [
        'default_commerce_id' => 'string',
    ];

    /**
     * Comercio marcado como principal/vinculado a la app.
     */
    public function defaultCommerce(): BelongsTo
    {
        return $this->belongsTo(Commerce::class, 'default_commerce_id');
    }
}

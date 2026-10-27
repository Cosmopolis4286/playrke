<?php

namespace App\Models;

use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para parámetros de configuración de la aplicación
 *
 * Representa parámetros de configuración del sistema con sus valores y estados.
 *
 *
 * @property string         $id         ID único del parámetro
 * @property string         $param      Nombre del parámetro de configuración
 * @property string|null    $value      Valor del parámetro
 * @property string         $status     Estado del parámetro
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 *
 * @method static Builder|AppParameter active()
 * @method static Builder|AppParameter inactive()
 * @method static Builder|AppParameter byParam(string $param)
 * @method static Builder|AppParameter byValue(string $value)
 * @method static Builder|AppParameter criticalSystem()
 */
class AppParameter extends Model
{
    /**
     * Parámetros críticos del sistema
     */
    public const CRITICAL_PARAMS = [
        'app_env',
        'app_debug',
        'maintenance_mode',
        'api_enabled',
        'database_status',
        'cache_enabled',
        'queue_enabled',
        'security_level',
    ];

    /**
     * Estados disponibles para parámetros
     */
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Activo',
        self::STATUS_INACTIVE => 'Inactivo',
    ];

    public $incrementing = false;

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'id' => 'string',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'param',
        'value',
        'status',
    ];
    protected $keyType = 'string';
    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'app_parameters';

    /**
     * Sobrescribe el boot del modelo para asignar Snowflake ID
     */
    protected static function booted() : void
    {
        static::creating(function(AppParameter $parameter) {
            if (! $parameter->id) {
                $parameter->id = (new Snowflake)->id();
            }
        });
    }

    /**
     * Obtiene el valor formateado del parámetro
     */
    public function getFormattedValue() : string
    {
        return $this->value ?? '';
    }

    /**
     * Obtiene la descripción del estado
     */
    public function getStatusDescription() : string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Verifica si el parámetro está activo
     */
    public function isActive() : bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Verifica si el parámetro es crítico del sistema
     */
    public function isCriticalSystemParam() : bool
    {
        return in_array($this->param, self::CRITICAL_PARAMS, true);
    }

    /**
     * Verifica si el parámetro está inactivo
     */
    public function isInactive() : bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * Scope para obtener solo parámetros activos
     */
    public function scopeActive(Builder $query) : Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope para buscar por parámetro
     */
    public function scopeByParam(Builder $query, string $param) : Builder
    {
        return $query->where('param', $param);
    }

    /**
     * Scope para buscar por valor
     */
    public function scopeByValue(Builder $query, string $value) : Builder
    {
        return $query->where('value', $value);
    }

    /**
     * Scope para parámetros críticos del sistema
     */
    public function scopeCriticalSystem(Builder $query) : Builder
    {
        return $query->whereIn('param', self::CRITICAL_PARAMS);
    }

    /**
     * Scope para obtener solo parámetros inactivos
     */
    public function scopeInactive(Builder $query) : Builder
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }
}

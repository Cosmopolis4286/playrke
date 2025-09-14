<?php

declare(strict_types=1);

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use BradieTilley\Snowflake\Snowflake;

/**
 * Modelo para configuraciones del sistema
 *
 * Representa parámetros de configuración del sistema con sus valores y estados.
 *
 * @package App\Models\Settings
 *
 * @property string $id ID único de la configuración
 * @property string $param Nombre del parámetro de configuración
 * @property string|null $value Valor del parámetro
 * @property string $status Estado de la configuración
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 */
class Setting extends Model
{
    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'settings';

    /**
     * Estados disponibles para configuraciones
     */
    public const STATUSES = [
        'ACTIVE' => 'Activo',
        'INACTIVE' => 'Inactivo',
    ];

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'param',
        'value',
        'status',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Sobrescribe el boot del modelo para asignar Snowflake ID
     */
    protected static function boot(): void
    {
        parent::boot();

        // Generar ID con Snowflake antes de crear el registro
        static::creating(function (Setting $setting) {
            if (!$setting->id) {
                $setting->id = (new Snowflake())->id();
            }
        });
    }

    /**
     * Obtiene la descripción del estado
     */
    public function getStatusDescription(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Verifica si la configuración está activa
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Verifica si la configuración está inactiva
     */
    public function isInactive(): bool
    {
        return $this->status === 'INACTIVE';
    }

    /**
     * Obtiene el valor formateado de la configuración
     */
    public function getFormattedValue(): string
    {
        return $this->value ?? '';
    }

    /**
     * Verifica si el parámetro es crítico del sistema
     */
    public function isCriticalSystemParam(): bool
    {
        $criticalParams = [
            'app_env',
            'app_debug',
            'maintenance_mode',
            'api_enabled',
            'database_status',
            'cache_enabled',
            'queue_enabled',
            'security_level'
        ];

        return in_array($this->param, $criticalParams);
    }

    /**
     * Scope para obtener solo configuraciones activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope para obtener solo configuraciones inactivas
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'INACTIVE');
    }

    /**
     * Scope para buscar por parámetro
     */
    public function scopeByParam($query, string $param)
    {
        return $query->where('param', $param);
    }

    /**
     * Scope para buscar por valor
     */
    public function scopeByValue($query, string $value)
    {
        return $query->where('value', $value);
    }

    /**
     * Scope para configuraciones críticas del sistema
     */
    public function scopeCriticalSystem($query)
    {
        $criticalParams = [
            'app_env',
            'app_debug',
            'maintenance_mode',
            'api_enabled',
            'database_status',
            'cache_enabled',
            'queue_enabled',
            'security_level'
        ];

        return $query->whereIn('param', $criticalParams);
    }
}

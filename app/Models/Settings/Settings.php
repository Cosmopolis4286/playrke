<?php

declare(strict_types=1);

namespace App\Models\Settings;

use App\Traits\HasSnowflakeId;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para configuraciones del sistema
 *
 * Representa parámetros de configuración del sistema con sus valores y estados.
 * Permite gestionar configuraciones dinámicas sin necesidad de archivos.
 *
 * @package App\Models\Settings
 * @author Sistema de Gestión de Configuraciones
 * @version 1.0
 *
 * @property string $id ID único de la configuración
 * @property string $param Nombre del parámetro de configuración
 * @property string|null $value Valor del parámetro
 * @property string $status Estado de la configuración
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 */
class Settings extends Model
{
    use HasSnowflakeId;

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
     * Obtiene la descripción del estado
     *
     * @return string
     */
    public function getStatusDescription(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Verifica si la configuración está activa
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Verifica si la configuración está inactiva
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status === 'INACTIVE';
    }

    /**
     * Obtiene el valor formateado de la configuración
     *
     * @return string
     */
    public function getFormattedValue(): string
    {
        return $this->value ?? '';
    }

    /**
     * Verifica si el parámetro es crítico del sistema
     *
     * @return bool
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
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope para obtener solo configuraciones inactivas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'INACTIVE');
    }

    /**
     * Scope para buscar por parámetro
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $param
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByParam($query, string $param)
    {
        return $query->where('param', $param);
    }

    /**
     * Scope para buscar por valor
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByValue($query, string $value)
    {
        return $query->where('value', $value);
    }

    /**
     * Scope para configuraciones críticas del sistema
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
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

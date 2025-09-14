<?php

namespace App\Models\Games\Shared;

use Illuminate\Database\Eloquent\Model;
use BradieTilley\Snowflake\Snowflake;

/**
 * Modelo Game refactorizado
 *
 * Solo contiene:
 * - Configuración de la tabla y propiedades
 * - Boot methods para Snowflake
 * - Accessors y Mutators si es necesario
 * - Scopes útiles
 *
 * NOTA: Las consultas complejas se han movido a GameRepository
 */
class Game extends Model
{
    // ========================
    // Configuración de la tabla
    // ========================
    protected $table = 'games';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // ========================
    // Campos asignables
    // ========================
    protected $fillable = [
        'name',
        'status',
    ];

    // ========================
    // Casting de tipos
    // ========================
    protected $casts = [
        'name' => 'string',
        'status' => 'string',
    ];

    // ========================
    // Boot method para Snowflake
    // ========================
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Game $game) {
            if (!$game->id) {
                $game->id = (new Snowflake())->id();
            }
        });
    }

    // ========================
    // Accessors
    // ========================

    /**
     * Verificar si el juego está activo
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Verificar si el juego está inactivo
     */
    public function getIsInactiveAttribute(): bool
    {
        return $this->status === 'INACTIVE';
    }

    /**
     * Verificar si el juego está en mantenimiento
     */
    public function getIsInMaintenanceAttribute(): bool
    {
        return $this->status === 'MAINTENANCE';
    }

    /**
     * Verificar si el juego está disponible (activo)
     */
    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Obtener nombre para mostrar
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Obtener clase CSS según el estado
     */
    public function getStatusClassAttribute(): string
    {
        return match($this->status) {
            'ACTIVE' => 'text-green-600',
            'INACTIVE' => 'text-red-600',
            'MAINTENANCE' => 'text-yellow-600',
            default => 'text-gray-600',
        };
    }

    /**
     * Obtener texto del estado en español
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'ACTIVE' => 'Activo',
            'INACTIVE' => 'Inactivo',
            'MAINTENANCE' => 'Mantenimiento',
            default => 'Desconocido',
        };
    }

    // ========================
    // Scopes útiles
    // ========================

    /**
     * Scope para juegos activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope para juegos inactivos
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'INACTIVE');
    }

    /**
     * Scope para juegos en mantenimiento
     */
    public function scopeInMaintenance($query)
    {
        return $query->where('status', 'MAINTENANCE');
    }

    /**
     * Scope para ordenar por nombre
     */
    public function scopeOrderedByName($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Scope para buscar por nombre
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Scope para buscar por estado
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', strtoupper($status));
    }

    /**
     * Scope para juegos disponibles (activos)
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'ACTIVE');
    }
}

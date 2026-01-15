<?php

namespace App\Models;

use App\Models\Users\Player\External\ExternalPlayers;
use App\Models\Users\Seller\Seller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use BradieTilley\Snowflake\Snowflake;

/**
 * Modelo Country refactorizado
 *
 * Solo contiene:
 * - Configuración de la tabla y propiedades
 * - Relaciones Eloquent
 * - Boot methods para Snowflake
 * - Accessors y Mutators si es necesario
 * - Scopes útiles
 *
 * NOTA: Las consultas complejas se han movido a CountryRepository
 */
class Country extends Model
{
    // ========================
    // Configuración de la tabla
    // ========================
    protected $table = 'countries';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // ========================
    // Campos asignables
    // ========================
    protected $fillable = [
        'name',
        'iso_code_2',
        'iso_code_3',
        'status',
    ];

    // ========================
    // Casting de tipos
    // ========================
    protected $casts = [
        'id' => 'string',
        'iso_code_2' => 'string',
        'iso_code_3' => 'string',
        'status' => 'string',
    ];

    // ========================
    // Boot method para Snowflake
    // ========================
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Country $country) {
            if (!$country->id) {
                $country->id = (new Snowflake())->id();
            }
        });
    }

    // ========================
    // Relaciones
    // ========================

    /**
     * Relación: un país tiene muchos vendedores
     */
    public function sellers(): HasMany
    {
        return $this->hasMany(Seller::class, 'country_id');
    }

    /**
     * Relación: un país tiene muchos jugadores externos
     */
    public function externalPlayers(): HasMany
    {
        return $this->hasMany(ExternalPlayers::class, 'country_id');
    }

    // ========================
    // Accessors
    // ========================

    /**
     * Verificar si el país está activo
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Verificar si el país está inactivo
     */
    public function getIsInactiveAttribute(): bool
    {
        return $this->status === 'INACTIVE';
    }

    /**
     * Verificar si el país está bloqueado
     */
    public function getIsBlockedAttribute(): bool
    {
        return $this->status === 'BLOCKED';
    }

    /**
     * Obtener nombre para mostrar con códigos ISO
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->iso_code_2})";
    }

    // ========================
    // Scopes útiles
    // ========================

    /**
     * Scope para países activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope para países inactivos
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'INACTIVE');
    }

    /**
     * Scope para países bloqueados
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', 'BLOCKED');
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
     * Scope para buscar por código ISO
     */
    public function scopeByIsoCode($query, string $isoCode)
    {
        $isoCode = strtoupper($isoCode);
        return $query->where('iso_code_2', $isoCode)
                    ->orWhere('iso_code_3', $isoCode);
    }

    /**
     * Scope para países con vendedores
     */
    public function scopeWithSellers($query)
    {
        return $query->whereHas('sellers');
    }

    /**
     * Scope para países con jugadores externos
     */
    public function scopeWithExternalPlayers($query)
    {
        return $query->whereHas('externalPlayers');
    }
}

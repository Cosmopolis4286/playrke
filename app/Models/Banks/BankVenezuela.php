<?php

namespace App\Models\Banks;

use App\Models\Payments\Methods\Venezuela\PagoMovil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use BradieTilley\Snowflake\Snowflake;

/**
 * Modelo BankVenezuela refactorizado
 *
 * Solo contiene:
 * - Configuración de la tabla y propiedades
 * - Relaciones Eloquent
 * - Boot methods para Snowflake
 * - Accessors y Mutators si es necesario
 *
 * NOTA: Las consultas complejas se han movido a BankVenezuelaRepository
 */
class BankVenezuela extends Model
{
    // ========================
    // Configuración de la tabla
    // ========================
    protected $table = 'banks_venezuela';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // ========================
    // Campos asignables
    // ========================
    protected $fillable = [
        'name',
        'code',
    ];

    // ========================
    // Casting de tipos
    // ========================
    protected $casts = [
        'code' => 'string',
    ];

    // ========================
    // Boot method para Snowflake
    // ========================
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (BankVenezuela $bank) {
            if (!$bank->id) {
                $bank->id = (new Snowflake())->id();
            }
        });
    }

    // ========================
    // Relaciones
    // ========================

    /**
     * Relación con los pagos móviles asociados a este banco
     */
    public function pagoMoviles(): HasMany
    {
        return $this->hasMany(PagoMovil::class, 'bank_id');
    }

    // ========================
    // Accessors
    // ========================

    /**
     * Obtener nombre para mostrar
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    // ========================
    // Scopes útiles
    // ========================

    /**
     * Scope para buscar por nombre
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Scope para ordenar por nombre
     */
    public function scopeOrderedByName($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Scope para bancos con pagos móviles
     */
    public function scopeWithPagoMovil($query)
    {
        return $query->whereHas('pagoMoviles');
    }
}

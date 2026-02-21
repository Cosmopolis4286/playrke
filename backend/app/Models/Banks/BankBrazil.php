<?php

namespace App\Models\Banks;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo BankBrazil refactorizado
 *
 * Solo contiene:
 * - Configuración de la tabla y propiedades
 * - Boot methods para Snowflake
 * - Accessors y Mutators si es necesario
 *
 * NOTA: Las consultas complejas se han movido a BankBrazilRepository
 */
class BankBrazil extends Model
{
    use SnowflakeId;

    // ========================
    // Configuración de la tabla
    // ========================
    protected $table = 'banks_brazil';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // ========================
    // Campos asignables
    // ========================
    protected $fillable = [
        'name',
        'full_name',
        'ispb',
    ];

    // ========================
    // Casting de tipos
    // ========================
    protected $casts = [
        'id'   => 'string',
        'ispb' => 'string',
    ];

    // ========================
    // Accessors
    // ========================

    /**
     * Obtener nombre completo formateado
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: $this->name;
    }

    // ========================
    // Scopes útiles
    // ========================

    /**
     * Scope para buscar por nombre
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%")
                    ->orWhere('full_name', 'like', "%{$name}%");
    }

    /**
     * Scope para ordenar por nombre
     */
    public function scopeOrderedByName($query)
    {
        return $query->orderBy('name');
    }
}

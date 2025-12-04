<?php

namespace App\Models\Banks;

use App\Models\Payments\Methods\Venezuela\PagoMovil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\SnowflakeId;

/**
 * Modelo BankVenezuela
 *
 * - Usa Snowflake mediante trait
 * - Sin lógica de negocio
 * - Relaciones, scopes, casts y fillables
 */
class BankVenezuela extends Model
{
    use SnowflakeId;

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
    // Casting
    // ========================
    protected $casts = [
        'code' => 'string',
    ];

    // ========================
    // Relaciones
    // ========================
    public function pagoMoviles(): HasMany
    {
        return $this->hasMany(PagoMovil::class, 'bank_id');
    }

    // ========================
    // Accessors
    // ========================
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    // ========================
    // Scopes
    // ========================
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public function scopeOrderedByName($query)
    {
        return $query->orderBy('name');
    }

    public function scopeWithPagoMovil($query)
    {
        return $query->whereHas('pagoMoviles');
    }
}

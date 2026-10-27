<?php

namespace App\Models\Banks;

use App\Models\Directories\Fiat\Venezuela\PagoMovil;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    public $incrementing = false;

    // ========================
    // Casting
    // ========================
    protected $casts = [
        'id' => 'string',
        'code' => 'string',
    ];

    // ========================
    // Campos asignables
    // ========================
    protected $fillable = [
        'name',
        'code',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    // ========================
    // Configuración de la tabla
    // ========================
    protected $table = 'banks_venezuela';

    // ========================
    // Accessors
    // ========================
    public function getDisplayNameAttribute() : string
    {
        return $this->name;
    }

    // ========================
    // Relaciones
    // ========================
    public function pagoMoviles() : HasMany
    {
        return $this->hasMany(PagoMovil::class, 'bank_id');
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

<?php

namespace App\Models\Roles;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\Roles\AdminFactory;

/**
 * Modelo para administradores del sistema
 */
class Admin extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'unique_code_id',
        'country_id',
        'currency_id',
        'status',
        'permissions',
        'last_login_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'last_login_at' => 'datetime',
    ];

    /**
     * Obtiene el usuario asociado al administrador
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Obtiene el código único asociado al administrador
     */
    public function uniqueCode()
    {
        return $this->belongsTo(\App\Models\UniqueCode::class, 'unique_code_id');
    }

    /**
     * Obtiene el país del administrador
     */
    public function country()
    {
        return $this->belongsTo(\App\Models\Country::class);
    }

    /**
     * Obtiene la moneda del administrador
     */
    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class);
    }

    /**
     * Verifica si el administrador está activo
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Verifica si el administrador está bloqueado
     */
    public function isBlocked(): bool
    {
        return $this->status === 'BLOCKED';
    }

    /**
     * Verifica si el administrador tiene un permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }
}

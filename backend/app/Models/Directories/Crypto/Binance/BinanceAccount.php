<?php

namespace App\Models\Directories\Crypto\Binance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo BinanceAccount refactorizado
 *
 * Solo contiene:
 * - Configuración de la tabla y propiedades
 * - Relaciones Eloquent
 * - Boot methods para Snowflake
 * - Accessors y Mutators si es necesario
 * - Scopes útiles
 * - Constantes
 *
 * NOTA: Las consultas complejas se han movido a BinanceAccountsRepository
 */
class BinanceAccount extends Model
{
    use \App\Traits\SnowflakeId;

    // ========================
    // Constantes
    // ========================
    public const TYPES = [
        'E' => 'E-mail',
        'P' => 'Num. Celular',
        'B' => 'Id de Binance',
    ];
    public $incrementing = false;

    // ========================
    // Accessors añadidos automáticamente
    // ========================
    protected $appends = [
        'account_type_description',
    ];

    // ========================
    // Casting de tipos
    // ========================
    protected $casts = [
        'account_type' => 'string',
        'data_value' => 'string',
        'status' => 'string',
    ];

    // ========================
    // Campos asignables
    // ========================
    protected $fillable = [
        'account_type',
        'data_value',
        'user_id',
        'status',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    // ========================
    // Configuración de la tabla
    // ========================
    protected $table = 'binance_accounts';

    // ========================
    // Métodos estáticos útiles
    // ========================

    /**
     * Obtener tipos de cuenta disponibles
     *
     * @param string|null $excludeType Tipo a excluir
     */
    public static function getAccountTypes(?string $excludeType = null) : array
    {
        if ($excludeType) {
            return array_filter(self::TYPES, function($key) use ($excludeType) {
                return $key !== $excludeType;
            }, ARRAY_FILTER_USE_KEY);
        }

        return self::TYPES;
    }

    // ========================
    // Accessors
    // ========================

    /**
     * Obtener descripción del tipo de cuenta
     */
    public function getAccountTypeDescriptionAttribute() : string
    {
        return self::TYPES[$this->account_type] ?? 'Desconocido';
    }

    /**
     * Obtener nombre para mostrar
     */
    public function getDisplayNameAttribute() : string
    {
        return "{$this->account_type_description}: {$this->data_value}";
    }

    /**
     * Verificar si la cuenta está activa
     */
    public function getIsActiveAttribute() : bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Verificar si es cuenta de ID Binance
     */
    public function getIsBinanceIdAttribute() : bool
    {
        return $this->account_type === 'B';
    }

    /**
     * Verificar si la cuenta está bloqueada
     */
    public function getIsBlockedAttribute() : bool
    {
        return $this->status === 'BLOCKED';
    }

    /**
     * Verificar si es cuenta de email
     */
    public function getIsEmailAttribute() : bool
    {
        return $this->account_type === 'E';
    }

    /**
     * Verificar si la cuenta está inactiva
     */
    public function getIsInactiveAttribute() : bool
    {
        return $this->status === 'INACTIVE';
    }

    /**
     * Verificar si es cuenta de teléfono
     */
    public function getIsPhoneAttribute() : bool
    {
        return $this->account_type === 'P';
    }

    /**
     * Obtener clase CSS según el estado
     */
    public function getStatusClassAttribute() : string
    {
        return match ($this->status) {
            'ACTIVE' => 'text-green-600',
            'INACTIVE' => 'text-yellow-600',
            'BLOCKED' => 'text-red-600',
            default => 'text-gray-600',
        };
    }

    /**
     * Obtener texto del estado en español
     */
    public function getStatusTextAttribute() : string
    {
        return match ($this->status) {
            'ACTIVE' => 'Activa',
            'INACTIVE' => 'Inactiva',
            'BLOCKED' => 'Bloqueada',
            default => 'Desconocido',
        };
    }

    // ========================
    // Scopes útiles
    // ========================

    /**
     * Scope para cuentas activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope para cuentas de ID Binance
     */
    public function scopeBinanceId($query)
    {
        return $query->where('account_type', 'B');
    }

    /**
     * Scope para cuentas bloqueadas
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', 'BLOCKED');
    }

    /**
     * Scope para buscar por tipo de cuenta
     */
    public function scopeByAccountType($query, string $accountType)
    {
        return $query->where('account_type', strtoupper($accountType));
    }

    /**
     * Scope para buscar por valor de datos
     */
    public function scopeByDataValue($query, string $dataValue)
    {
        return $query->where('data_value', $dataValue);
    }

    /**
     * Scope para buscar por estado
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', strtoupper($status));
    }

    /**
     * Scope para cuentas de email
     */
    public function scopeEmail($query)
    {
        return $query->where('account_type', 'E');
    }

    /**
     * Scope para buscar por usuario
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para cuentas inactivas
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'INACTIVE');
    }

    /**
     * Scope para ordenar por fecha de creación
     */
    public function scopeOrderedByCreation($query, string $direction = 'desc')
    {
        return $query->orderBy('created_at', $direction);
    }

    /**
     * Scope para cuentas de teléfono
     */
    public function scopePhone($query)
    {
        return $query->where('account_type', 'P');
    }

    /**
     * Scope para buscar con usuario eager loaded
     */
    public function scopeWithUser($query)
    {
        return $query->with('user');
    }

    // ========================
    // Relaciones
    // ========================

    /**
     * Relación: una cuenta Binance pertenece a un usuario
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

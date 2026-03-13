<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Modelo para preferencias de usuario
 *
 * Representa configuraciones y preferencias personalizadas de usuarios
 * con soporte para valores por defecto y categorías.
 *
 * @package App\Models
 *
 * @property string $id ID único de la preferencia
 * @property string|null $user_id ID del usuario (null para globales)
 * @property string $category Categoría de la preferencia
 * @property string $key Clave de la preferencia
 * @property string|null $value Valor de la preferencia
 * @property string $type Tipo de dato
 * @property string $status Estado de la preferencia
 * @property int $sort_order Orden de visualización
 * @property string|null $description Descripción
 * @property array|null $allowed_values Valores permitidos
 * @property string|null $default_value Valor por defecto
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación
 * 
 * @property-read User|null $user Usuario relacionado
 *
 * @method static Builder|UserPreference active()
 * @method static Builder|UserPreference inactive()
 * @method static Builder|UserPreference byUser($userId)
 * @method static Builder|UserPreference byCategory(string $category)
 * @method static Builder|UserPreference byKey(string $key)
 * @method static Builder|UserPreference global()
 * @method static Builder|UserPreference forUser($userId)
 */
class UserPreference extends BaseModel
{

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'user_preferences';

    /**
     * Estados disponibles para preferencias
     */
    public const STATUS_ACTIVE   = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    public const STATUSES = [
        self::STATUS_ACTIVE   => 'Activo',
        self::STATUS_INACTIVE => 'Inactivo',
    ];

    /**
     * Tipos de datos disponibles
     */
    public const TYPE_STRING  = 'string';
    public const TYPE_NUMBER  = 'number';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_JSON    = 'json';
    public const TYPE_ARRAY   = 'array';

    public const TYPES = [
        self::TYPE_STRING  => 'Texto',
        self::TYPE_NUMBER  => 'Número',
        self::TYPE_BOOLEAN => 'Booleano',
        self::TYPE_JSON    => 'JSON',
        self::TYPE_ARRAY   => 'Arreglo',
    ];

    /**
     * Categorías predefinidas
     */
    public const CATEGORY_UI = 'ui';
    public const CATEGORY_NOTIFICATIONS = 'notifications';
    public const CATEGORY_EXCHANGE_RATE = 'exchange_rate';
    public const CATEGORY_SECURITY = 'security';
    public const CATEGORY_GENERAL = 'general';

    public const CATEGORIES = [
        self::CATEGORY_UI => 'Interfaz',
        self::CATEGORY_NOTIFICATIONS => 'Notificaciones',
        self::CATEGORY_EXCHANGE_RATE => 'Tasas de Cambio',
        self::CATEGORY_SECURITY => 'Seguridad',
        self::CATEGORY_GENERAL => 'General',
    ];

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'user_id',
        'category',
        'key',
        'value',
        'type',
        'status',
        'sort_order',
        'description',
        'allowed_values',
        'default_value',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'allowed_values' => 'array',
        'sort_order' => 'integer',
        'status' => 'string',
        'type' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con el usuario
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene el valor tipado de la preferencia
     */
    public function getTypedValue(): mixed
    {
        return match ($this->type) {
            self::TYPE_BOOLEAN => $this->value === '1' || $this->value === 'true' || $this->value === true,
            self::TYPE_NUMBER => is_numeric($this->value) ? (float) $this->value : 0,
            self::TYPE_JSON => json_decode($this->value ?? '{}', true),
            self::TYPE_ARRAY => is_array($this->value) ? $this->value : explode(',', $this->value ?? ''),
            default => $this->value,
        };
    }

    /**
     * Establece el valor tipado de la preferencia
     */
    public function setTypedValue(mixed $value): void
    {
        $this->value = match ($this->type) {
            self::TYPE_BOOLEAN => $value ? '1' : '0', // Guardar como '1'/'0' para MySQL
            self::TYPE_JSON => json_encode($value),
            self::TYPE_ARRAY => is_array($value) ? implode(',', $value) : $value,
            default => (string) $value,
        };
    }

    /**
     * Obtiene el valor formateado para mostrar
     */
    public function getFormattedValue(): string
    {
        return match ($this->type) {
            self::TYPE_BOOLEAN => $this->getTypedValue() ? 'Sí' : 'No',
            self::TYPE_NUMBER => number_format($this->getTypedValue(), 2),
            self::TYPE_JSON => json_encode($this->getTypedValue(), JSON_PRETTY_PRINT),
            default => (string) ($this->value ?? $this->default_value ?? ''),
        };
    }

    /**
     * Obtiene la descripción del estado
     */
    public function getStatusDescription(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Obtiene la descripción del tipo
     */
    public function getTypeDescription(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Obtiene la descripción de la categoría
     */
    public function getCategoryDescription(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Verifica si la preferencia está activa
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Verifica si la preferencia está inactiva
     */
    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * Verifica si es una preferencia global
     */
    public function isGlobal(): bool
    {
        return is_null($this->user_id);
    }

    /**
     * Verifica si un valor es permitido
     */
    public function isValueAllowed(mixed $value): bool
    {
        if (is_null($this->allowed_values) || empty($this->allowed_values)) {
            return true;
        }

        return in_array($value, $this->allowed_values);
    }

    /**
     * Obtiene el valor efectivo (valor del usuario o valor por defecto)
     */
    public function getEffectiveValue(): mixed
    {
        return $this->value ?? $this->default_value;
    }

    /**
     * Scope para obtener solo preferencias activas
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope para obtener solo preferencias inactivas
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Scope para filtrar por usuario
     */
    public function scopeByUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope para filtrar por clave
     */
    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    /**
     * Scope para obtener preferencias globales
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope para obtener preferencias para un usuario (incluyendo globales)
     */
    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)->orWhereNull('user_id');
        });
    }

    /**
     * Obtiene una preferencia específica para un usuario
     */
    public static function getPreference($userId, string $category, string $key, mixed $default = null): mixed
    {
        $preference = static::forUser($userId)
            ->byCategory($category)
            ->byKey($key)
            ->active()
            ->orderByRaw('user_id DESC') // Priorizar preferencias de usuario sobre globales
            ->first();

        if (!$preference) {
            return $default;
        }

        return $preference->getEffectiveValue() ?? $default;
    }

    /**
     * Establece una preferencia para un usuario
     */
    public static function setPreference($userId, string $category, string $key, mixed $value): bool
    {
        try {
            $preference = static::updateOrCreate(
                [
                    'user_id' => $userId,
                    'category' => $category,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                    'status' => self::STATUS_ACTIVE,
                ]
            );

            return $preference->exists;
        } catch (\Exception $e) {
            return false;
        }
    }
}

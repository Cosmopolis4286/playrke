<?php

namespace App\Models;

use App\Models\Concerns\HasHashedRouteKey;
use App\Models\Country;
use App\Models\Roles\Company;
use App\Models\Role;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Wallet;
use App\Traits\Auditable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo User con Snowflake IDs
 *
 * Este modelo representa los usuarios del sistema, incluyendo roles,
 * referencias, entidades relacionadas y helpers de acceso.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, HasRoles;
    use SoftDeletes;
    use Auditable;
    use HasHashedRouteKey;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'users';

    /**
     * Clave primaria del modelo
     */
    protected $primaryKey = 'id';

    /**
     * Indica si el ID es auto-incremental
     */
    public $incrementing = false;

    /**
     * Tipo de la clave primaria
     */
    protected $keyType = 'string';

    /**
     * Estados disponibles para usuarios
     */
    public const STATUSES = [
        'ACTIVE' => 'Activo',
        'INACTIVE' => 'Inactivo',
        'SUSPENDED' => 'Suspendido',
        'PENDING_VERIFICATION' => 'Pendiente de verificación',
    ];

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'password',
        'profile_photo_url',
        'email',
        'phone_number',
        'country_id',
        'time_zone_id',
        'referral_code',
        'referred_by_user_id',
        'email_verified_at',
        'status',
        'is_demo_user',
        'demo_password',
        'last_login_at',
        'last_login_ip',
        'is_email_notifications_enabled',
        'is_two_factor_enabled',
        'user_preferences',
    ];

    /**
     * Campos ocultos en serialización JSON
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'country_id' => 'string',
        'time_zone_id' => 'string',
        'is_demo_user' => 'boolean',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_email_notifications_enabled' => 'boolean',
        'is_two_factor_enabled' => 'boolean',
        'user_preferences' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Campos adicionales que se incluyen en las respuestas JSON
     */
    protected $appends = [
        'full_name',
    ];

    /**
     * Sobrescribe el boot del modelo para asignar Snowflake ID
     */
    protected static function boot(): void
    {
        parent::boot();

        // Generar ID con Snowflake antes de crear el registro
        static::creating(function (User $user) {
            if (!$user->id) {
                $user->id = (new Snowflake())->id();
            }
        });
    }

    /**
     * Roles del usuario
     *
     * @return BelongsToMany<Role>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    /**
     * Usuario que refirió a este usuario
     *
     * @return BelongsTo<User, User>
     */
    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_user_id');
    }

    /**
     * Usuarios referidos por este usuario
     *
     * @return HasMany<User>
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_user_id');
    }

    /**
     * País al que pertenece el usuario
     *
     * @return BelongsTo<Country, self>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Zona horaria del usuario
     *
     * @return BelongsTo<\App\Models\TimeZone, self>
     */
    public function timeZone(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TimeZone::class, 'time_zone_id');
    }

    /**
     * Wallets del usuario
     *
     * @return HasMany<Wallet>
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'user_id');
    }

    /**
     * Comercio asociado (si rol commerce)
     *
     * @return HasOne<Commerce>
     */
    public function commerce(): HasOne
    {
        return $this->hasOne(Commerce::class, 'user_id');
    }

    /**
     * Vendedor asociado (si rol seller)
     *
     * @return HasOne<Seller>
     */
    public function seller(): HasOne
    {
        return $this->hasOne(Seller::class, 'user_id');
    }

    /**
     * Compañías asociadas (si aplica)
     *
     * @return BelongsToMany<Company>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot('role_id')
            ->withTimestamps();
    }

    /**
     * Obtiene la descripción del estado
     *
     * @return string
     */
    public function getStatusDescriptionAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status ?? 'Sin estado';
    }

    /**
     * Nombre completo del usuario
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Verifica si el usuario está activo
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Verifica si el usuario está inactivo
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status === 'INACTIVE';
    }

    /**
     * Verifica si el usuario está suspendido
     *
     * @return bool
     */
    public function isSuspended(): bool
    {
        return $this->status === 'SUSPENDED';
    }

    /**
     * Verifica si el usuario está pendiente de verificación
     *
     * @return bool
     */
    public function isPendingVerification(): bool
    {
        return $this->status === 'PENDING_VERIFICATION';
    }

    /**
     * Verifica si el email del usuario está verificado
     *
     * @return bool
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Verifica si el usuario tiene autenticación de dos factores habilitada
     *
     * @return bool
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->is_two_factor_enabled;
    }

    /**
     * Obtiene el wallet de una moneda específica
     *
     * @param string $currencyCode Código ISO de la moneda
     * @return Wallet|null
     */
    public function walletByCurrency(string $currencyCode): ?Wallet
    {
        return $this->wallets()->whereHas('currency', fn($q) => $q->where('iso_code', $currencyCode))->first();
    }

    /**
     * Devuelve un array de entidades asociadas según los roles del usuario
     *
     * @return array
     */
    public function entities(): array
    {
        $entities = [];

        foreach ($this->roles as $role) {
            $entities[$role->prefix] = match ($role->prefix) {
                'commerce' => $this->commerce,
                'seller' => $this->seller,
                default => null,
            };
        }

        return $entities;
    }

    /**
     * Verifica si el usuario tiene un rol específico por prefijo
     *
     * @param string $prefix Prefijo del rol
     * @return bool
     */
    public function hasRolePrefix(string $prefix): bool
    {
        return $this->roles->contains('prefix', $prefix);
    }

    /**
     * Verifica si el usuario tiene un rol específico (por nombre o prefijo).
     *
     * Soporta:
     * - string: 'admin'
     * - string con coma: 'admin,commerce'
     * - array: ['admin', 'commerce']
     *
     * @param string|array $role Rol o roles a buscar.
     * @return bool
     */
    public function hasRole(string|array $role): bool
    {
        // Si viene como "admin,commerce"
        if (is_string($role) && str_contains($role, ',')) {
            $role = array_map('trim', explode(',', $role));
        }

        // Si es array, verificar si tiene alguno
        if (is_array($role)) {
            return $this->roles->contains(function ($r) use ($role) {
                return in_array($r->name, $role, true)
                    || in_array($r->prefix, $role, true);
            });
        }

        // Caso string normal
        return $this->roles->contains(function ($r) use ($role) {
            return $r->name === $role || $r->prefix === $role;
        });
    }

    /**
     * Scope para obtener solo usuarios activos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope para obtener solo usuarios inactivos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'INACTIVE');
    }

    /**
     * Scope para obtener usuarios por estado
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para obtener usuarios por país
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $countryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCountry($query, string $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope para obtener usuarios con email verificado
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEmailVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope para obtener usuarios con email no verificado
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEmailNotVerified($query)
    {
        return $query->whereNull('email_verified_at');
    }

    /**
     * Scope para obtener usuarios con rol específico
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $roleName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRole($query, string $roleName)
    {
        return $query->whereHas('roles', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    /**
     * Scope para buscar usuarios por texto
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%");
        });
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasHashedRouteKey;
use App\Models\Country;
use App\Models\Roles\Company;
use App\Models\Role;
use App\Models\Roles\Commerce;
use App\Models\Roles\Customer;
use App\Models\Roles\Seller;
use App\Models\Roles\Treasurer;
use App\Models\UserLevel;
use App\Models\Wallet;
use App\Models\Achievement;
use App\Traits\Auditable;
use App\Traits\SnowflakeId;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
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
    use Notifiable;
    use HasRoles {
        HasRoles::hasPermissionTo as protected spatieHasPermissionTo;
    }
    use SnowflakeId;
    use SoftDeletes;
    use Auditable;
    use HasFactory;
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
        'birth_date',
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
        'specific_permissions',
        'bio',
        'tos_accepted_version',
        'tos_accepted_at',
        'tos_accepted_ip',
        'kyc_level',
        'kyc_status',
        'kyc_verified_at',
        'kyc_rejection_reason',
        'is_system_account',
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
        'birth_date' => 'date',
        'is_demo_user'       => 'boolean',
        'is_system_account'  => 'boolean',
        'email_verified_at'  => 'datetime',
        'last_login_at' => 'datetime',
        'is_email_notifications_enabled' => 'boolean',
        'is_two_factor_enabled' => 'boolean',
        'user_preferences'      => 'array',
        'specific_permissions'  => 'array',
        'tos_accepted_at'       => 'datetime',
        'kyc_level'             => 'integer',
        'kyc_verified_at'       => 'datetime',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
    ];

    /**
     * Campos adicionales que se incluyen en las respuestas JSON
     */
    protected $appends = [
        'full_name',
    ];

    /**
     * Roles del usuario.
     *
     * Usa App\Models\Role (no Spatie\Permission\Models\Role): mismo registro,
     * pero App\Models\Role tiene el cast id => string via SnowflakeId. Usar el
     * modelo de Spatie aquí hidrataba el id con el cast por defecto de Eloquent
     * (int), truncando/perdiendo precisión del Snowflake y rompiendo cualquier
     * comparación estricta de id contra roles cargados por otra relación
     * (ej. Permission::roles(), que sí usa App\Models\Role).
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
     * Nivel y experiencia del usuario
     *
     * @return HasOne<UserLevel>
     */
    public function userLevel(): HasOne
    {
        return $this->hasOne(UserLevel::class);
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
     * Customer asociado (si rol customer)
     *
     * @return HasOne<Customer>
     */
    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class, 'user_id');
    }

    /**
     * Tesorero asociado (si rol treasurer)
     *
     * @return HasOne<Treasurer>
     */
    public function treasurer(): HasOne
    {
        return $this->hasOne(Treasurer::class, 'user_id');
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
            $entities[$role->prefix] = match (strtolower((string) $role->prefix)) {
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
     * - Role|iterable<Role>: instancia(s) de App\Models\Role — necesario porque
     *   Spatie\Permission\Traits\HasPermissions::hasPermissionViaRole() llama
     *   internamente $this->hasRole($permission->roles), pasando una Collection
     *   de objetos Role (no strings). Sin este caso, hasPermissionTo()/
     *   hasAnyPermission()/getAllPermissions() del trait HasRoles fallan
     *   silenciosamente para todo permiso heredado por rol, porque devuelven
     *   false en vez de lanzar un error.
     *
     * Fuente de verdad única para roles: reemplaza intencionalmente el
     * hasRole() del trait HasRoles (Spatie), que consulta model_has_roles
     * (tabla que esta app no usa). Todo middleware/gate/policy de rol debe
     * pasar por este método o por App\Services\Auth\RoleAuthorizationService,
     * nunca reimplementar la comparación de prefijos por su cuenta.
     *
     * @param string|array|\App\Models\Role|iterable $role Rol o roles a buscar.
     * @param string|null $guard Ignorado — este sistema no distingue roles por guard.
     *   Aceptado solo por compatibilidad de firma con Spatie\Permission\Traits\HasRoles::hasRole().
     * @return bool
     */
    public function hasRole($role, ?string $guard = null): bool
    {
        // Instancia única de Role: comparar directo por id.
        if ($role instanceof Role) {
            return $this->roles->contains('id', $role->id);
        }

        // Iterable de objetos Role (Collection, array de Role, etc.) — caso usado
        // internamente por Spatie al resolver permisos vía rol
        // (HasPermissions::hasPermissionViaRole() llama $this->hasRole($permission->roles)).
        // is_array() también es is_iterable(), así que este chequeo debe filtrar
        // por el tipo de los elementos: solo objetos Role, nunca strings.
        if (is_iterable($role) && !is_string($role)) {
            $items = collect($role);
            if ($items->isNotEmpty() && $items->every(fn ($r) => $r instanceof Role)) {
                $roleIds = $items->pluck('id')->all();
                return $this->roles->contains(fn ($r) => in_array($r->id, $roleIds, true));
            }
        }

        // "admin,commerce" → tratar como array de nombres/prefijos
        if (is_string($role) && str_contains($role, ',')) {
            return $this->hasRole(array_map('trim', explode(',', $role)));
        }

        // Array de nombres/prefijos: ['admin', 'commerce']
        if (is_array($role)) {
            $roleLower = array_map('strtolower', $role);
            return $this->roles->contains(function ($r) use ($roleLower) {
                return in_array(strtolower($r->name), $roleLower, true)
                    || in_array(strtolower((string) $r->prefix), $roleLower, true);
            });
        }

        // Caso string normal
        $roleLower = strtolower($role);
        return $this->roles->contains(function ($r) use ($roleLower) {
            return strtolower($r->name) === $roleLower
                || strtolower((string) $r->prefix) === $roleLower;
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
     * Scope para excluir cuentas internas del sistema (ej. playrke_platform)
     * de listados administrativos y reportes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExcludeSystemAccounts($query)
    {
        return $query->where('is_system_account', false);
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

    /**
     * Obtiene o crea el nivel del usuario
     *
     * @return UserLevel
     */
    public function getOrCreateLevel(): UserLevel
    {
        return $this->userLevel ?? $this->userLevel()->create([
            'current_level' => 1,
            'current_xp' => 0,
            'total_xp' => 0,
            'xp_to_next_level' => 1000,
        ]);
    }

    /**
     * Añade experiencia al usuario
     *
     * @param int $amount
     * @return bool
     */
    public function addXp(int $amount): bool
    {
        return $this->getOrCreateLevel()->addXp($amount);
    }

    /**
     * Obtiene el nivel actual del usuario
     *
     * @return int
     */
    public function getCurrentLevel(): int
    {
        return $this->userLevel?->current_level ?? 1;
    }

    /**
     * Obtiene el XP actual del usuario
     *
     * @return int
     */
    public function getCurrentXp(): int
    {
        return $this->userLevel?->current_xp ?? 0;
    }

    /**
     * Obtiene el progreso del nivel en porcentaje
     *
     * @return float
     */
    public function getLevelProgress(): float
    {
        return $this->userLevel?->progress_percentage ?? 0.0;
    }

    /**
     * Logros del usuario
     *
     * @return BelongsToMany<Achievement>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot(['unlocked_at', 'progress_data', 'is_seen'])
            ->withTimestamps();
    }

    /**
     * Logros desbloqueados del usuario
     *
     * @return BelongsToMany<Achievement>
     */
    public function unlockedAchievements(): BelongsToMany
    {
        return $this->achievements();
    }

    /**
     * Verifica si el usuario ha desbloqueado un logro específico
     *
     * @param string $achievementCode
     * @return bool
     */
    public function hasAchievement(string $achievementCode): bool
    {
        return $this->achievements()->where('code', $achievementCode)->exists();
    }

    /**
     * Obtiene el número total de logros desbloqueados
     *
     * @return int
     */
    public function getAchievementsCount(): int
    {
        return $this->achievements()->count();
    }

    /**
     * Obtiene los logros no vistos
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnseenAchievements()
    {
        return $this->achievements()->wherePivot('is_seen', false)->get();
    }

    /**
     * Marcar todos los logros como vistos
     *
     * @return int
     */
    public function markAllAchievementsAsSeen(): int
    {
        return $this->achievements()->newPivotStatement()
            ->where('user_id', $this->id)
            ->where('is_seen', false)
            ->update(['is_seen' => true]);
    }

    /**
     * Obtener todos los permisos del usuario como array de nombres: heredados
     * por rol (Spatie, vía roles()/role_has_permissions) + específicos del
     * usuario (specific_permissions). Fuente única para menús/gates que
     * necesiten la lista completa en vez de verificar un permiso puntual.
     *
     * @return array<int, string>
     */
    public function getAllPermissionNames(): array
    {
        $viaRoles = $this->getAllPermissions()->pluck('name')->all();

        return array_values(array_unique(array_merge($viaRoles, $this->specific_permissions ?? [])));
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     *
     * @param string $permission
     * @return bool
     */
    public function hasSpecificPermission(string $permission): bool
    {
        return in_array($permission, $this->specific_permissions ?? []);
    }

    /**
     * Determina si el usuario puede ejercer el permiso indicado, considerando
     * tanto los permisos heredados por rol (Spatie\Permission\Traits\HasPermissions,
     * ya operativo gracias al fix de roles()/hasRole() de más arriba) como los
     * permisos específicos otorgados directamente al usuario (specific_permissions,
     * usado por ej. para activar juegos concretos en un Commerce/Seller/Customer).
     *
     * Sobrescribe hasPermissionTo() del trait HasPermissions (Spatie) — de él
     * dependen hasAnyPermission(), hasAllPermissions(), checkPermissionTo() y,
     * por extensión, el helper can()/Gate::allows() de Laravel. Sobrescribir
     * aquí en el nivel más bajo evita reimplementar cada método por separado.
     *
     * @param string|int|\Spatie\Permission\Contracts\Permission|\BackedEnum $permission
     * @param string|null $guardName
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if (is_string($permission) && $this->hasSpecificPermission($permission)) {
            return true;
        }

        return $this->spatieHasPermissionTo($permission, $guardName);
    }

    /**
     * Agregar un permiso específico al usuario
     *
     * @param string|array $permissions
     * @return void
     */
    public function giveSpecificPermission($permissions): void
    {
        $currentPermissions = $this->specific_permissions ?? [];
        
        if (is_array($permissions)) {
            $newPermissions = array_unique(array_merge($currentPermissions, $permissions));
        } else {
            $newPermissions = array_unique(array_merge($currentPermissions, [$permissions]));
        }
        
        $this->update(['specific_permissions' => $newPermissions]);
    }

    /**
     * Quitar un permiso específico al usuario
     *
     * @param string|array $permissions
     * @return void
     */
    public function revokeSpecificPermission($permissions): void
    {
        $currentPermissions = $this->specific_permissions ?? [];
        
        if (is_array($permissions)) {
            $newPermissions = array_diff($currentPermissions, $permissions);
        } else {
            $newPermissions = array_diff($currentPermissions, [$permissions]);
        }
        
        $this->update(['specific_permissions' => array_values($newPermissions)]);
    }

    /**
     * Limpiar todos los permisos específicos del usuario
     *
     * @return void
     */
    public function clearSpecificPermissions(): void
    {
        $this->update(['specific_permissions' => []]);
    }

    public function hasTosAccepted(): bool
    {
        return $this->tos_accepted_version !== null;
    }

    public function tosNeedsRenewal(?string $currentVersion): bool
    {
        if ($currentVersion === null) {
            return false;
        }
        return $this->tos_accepted_version !== $currentVersion;
    }
}

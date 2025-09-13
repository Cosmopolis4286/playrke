<?php

namespace App\Models\Users;

use App\Models\Countries\Country;
use App\Models\Users\Roles\Commerce\Commerce;
use App\Models\Users\Roles\Company\Company;
use App\Models\Users\Roles\Role;
use App\Models\Users\Roles\Seller\Seller;
use App\Models\Wallets\Wallet;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo User con Snowflake IDs
 * 
 * Este modelo representa los usuarios del sistema, incluyendo roles,
 * referencias, entidades relacionadas y helpers de acceso.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, HasRoles;

    // ========================
    // Configuración de la tabla
    // ========================
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // ========================
    // Campos asignables
    // ========================
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
    ];

    // ========================
    // Campos ocultos y casts
    // ========================
    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // ========================
    // Accessors
    // ========================
    protected $appends = ['full_name'];

    // ========================
    // Boot method
    // ========================
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

    // ========================
    // Relaciones
    // ========================

    /**
     * Roles del usuario
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
     * Wallets del usuario
     *
     * Un usuario puede tener múltiples wallets (por moneda).
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'user_id', 'id');
    }

    /**
     * Devuelve el wallet de una moneda específica
     */
    public function walletByCurrency(string $currencyCode): ?Wallet
    {
        return $this->wallets()->whereHas('currency', fn($q) => $q->where('iso_code', $currencyCode))->first();
    }

    /**
     * Comercio asociado (si rol commerce)
     */
    public function commerce(): HasOne
    {
        return $this->hasOne(Commerce::class, 'user_id');
    }

    /**
     * Vendedor asociado (si rol seller)
     */
    public function seller(): HasOne
    {
        return $this->hasOne(Seller::class, 'user_id');
    }

    /**
     * Compañías asociadas (si aplica)
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot('role_id')
            ->withTimestamps();
    }

    // ========================
    // Accessors & Helpers
    // ========================

    /**
     * Nombre completo del usuario
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Devuelve un array de entidades asociadas según los roles del usuario
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
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRolePrefix(string $prefix): bool
    {
        return $this->roles->contains('prefix', $prefix);
    }
}

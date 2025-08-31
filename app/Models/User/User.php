<?php

namespace App\Models\User;

use App\Models\Countries\Country;
use App\Models\User\Roles\Commerce\Commerce;
use App\Models\User\Roles\Company\Company;
use App\Models\User\Roles\Role;
use App\Models\User\Roles\Seller\Seller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo User
 *
 * Representa a un usuario dentro del sistema, con soporte para relaciones
 * hacia otros modelos como Commerce, Seller, Roles, etc., y empresas (multiempresa).
 *
 * @property int         $id
 * @property string      $username
 * @property string      $first_name
 * @property string      $last_name
 * @property string      $password
 * @property string|null $photo
 * @property string      $email
 * @property string|null $phone
 * @property string      $country
 * @property string      $time_zone
 * @property string|null $referral_code
 * @property int|null    $referred_by
 * @property \DateTime|null $email_verified_at
 * @property-read string  $full_name
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property-read \App\Models\User|null $referredBy
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $referrals
 * @property-read \App\Models\Country|null $country
 * @property-read \App\Models\Commerce|null $commerce
 * @property-read \App\Models\Seller|null $seller
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Company[] $companies
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, HasRoles;

    protected $table = "users";
    protected $primaryKey = 'id';

    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'password',
        'photo',
        'email',
        'phone',
        'country',
        'time_zone',
        'referral_code',
        'referred_by',
        'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['email_verified_at' => 'datetime'];

    protected $appends = ['full_name'];

    /* ========================
     | Relaciones
     |======================== */

    /**
     * Roles del usuario (múltiples).
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Usuario que lo refirió.
     */
    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * Usuarios referidos por este usuario.
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /**
     * Relación con el país.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    /**
     * Relación con el comercio asociado.
     */
    public function commerce()
    {
        return $this->hasOne(Commerce::class, 'user_id');
    }

    /**
     * Relación con el vendedor asociado.
     */
    public function seller()
    {
        return $this->hasOne(Seller::class, 'user_id');
    }

    /**
     * Empresas a las que pertenece el usuario.
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class)
            ->withPivot('role_id')
            ->withTimestamps();
    }

    /* ========================
     | Accessors
     |======================== */

    /**
     * Nombre completo del usuario.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Obtener las entidades asociadas según los roles (commerce, seller, etc.).
     */
    public function entities(): array
    {
        $entities = [];
        foreach ($this->roles as $role) {
            $entities[$role->prefix] = match ($role->prefix) {
                'commerce_id' => $this->commerce,
                'seller'   => $this->seller,
                default    => null,
            };
        }
        return $entities;
    }
}

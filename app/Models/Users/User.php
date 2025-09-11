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
use Dive\Snowflake\HasSnowflake;

/**
 * Modelo User con Snowflake IDs
 *
 * @property string      $id
 * @property string      $username
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string      $email
 * @property string|null $phone
 * @property string|null $photo
 * @property string|null $referral_code
 * @property string|null $referred_by
 * @property \DateTime|null $email_verified_at
 * @property string      $status
 * @property-read string $full_name
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
    use Notifiable, HasRoles, HasSnowflake;

    protected $table = 'users';
    protected $primaryKey = 'id';

    // Snowflake IDs no incrementales
    public $incrementing = false;
    protected $keyType = 'string';

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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public function commerce()
    {
        return $this->hasOne(Commerce::class, 'user_id');
    }

    public function seller()
    {
        return $this->hasOne(Seller::class, 'user_id');
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class)
            ->withPivot('role_id')
            ->withTimestamps();
    }

    /* ========================
     | Accessors & Helpers
     |======================== */

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Devuelve las entidades relacionadas según los roles asignados
     *
     * @return array<string, mixed>
     */
    public function entities(): array
    {
        $entities = [];

        foreach ($this->roles as $role) {
            $entities[$role->prefix] = match ($role->prefix) {
                'commerce_id' => $this->commerce,
                'seller'      => $this->seller,
                default       => null,
            };
        }

        return $entities;
    }
}

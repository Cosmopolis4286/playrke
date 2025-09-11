<?php

namespace App\Models\Users;

use App\Models\Countries\Country;
use App\Models\Users\Roles\Commerce\Commerce;
use App\Models\Users\Roles\Company\Company;
use App\Models\Users\Roles\Role;
use App\Models\Users\Roles\Seller\Seller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo User con Snowflake IDs
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, HasRoles;

    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

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

    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['email_verified_at' => 'datetime'];
    protected $appends = ['full_name'];

    /* ========================
     | Boot method
     |======================== */
    protected static function boot()
    {
        parent::boot();

        // Generar ID con Snowflake antes de crear
        static::creating(function ($user) {
            if (!$user->id) {
                $user->id = (new Snowflake())->id();
            }
        });
    }

    /* ========================
     | Relaciones
     |======================== */

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
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

<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Modelo User
 *
 * Representa a un usuario dentro del sistema, con soporte para relaciones
 * hacia otros modelos como Commerce, Seller, UserType, etc.
 *
 * @property int         $id
 * @property string      $username
 * @property int         $user_type
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
 * @property-read string|null $user_type_description
 *
 * @property-read \App\Models\UserType|null $userType
 * @property-read \App\Models\User|null     $referredBy
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $referrals
 * @property-read \App\Models\Country|null  $countryRelation
 * @property-read \App\Models\Commerce|null $commerce
 * @property-read \App\Models\Seller|null   $seller
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    protected $table = "users";
    protected $primaryKey = 'id';

    /** @var array<int,string> */
    protected $fillable = [
        'username',
        'user_type',
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

    /** @var array<int,string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @var array<string,string> */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /** @var array<int,string> */
    protected $appends = ['user_type_description'];

    /* ========================
     |   Relaciones
     |======================== */

    /**
     * Relación con el tipo de usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userType()
    {
        return $this->belongsTo(UserType::class, 'user_type');
    }

    /**
     * Relación con el usuario que lo refirió.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * Relación con los usuarios referidos por este usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /**
     * Relación con el país.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function countryRelation()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    /**
     * Relación con el comercio asociado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function commerce()
    {
        return $this->belongsTo(Commerce::class, 'id', 'user');
    }

    /**
     * Relación con el vendedor asociado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function seller()
    {
        return $this->belongsTo(Seller::class, 'id', 'user');
    }

    /* ========================
     |   Accessors
     |======================== */

    /**
     * Obtener la descripción del tipo de usuario.
     *
     * @return string|null
     */
    public function getUserTypeDescriptionAttribute(): ?string
    {
        return optional($this->userType)->description;
    }
}

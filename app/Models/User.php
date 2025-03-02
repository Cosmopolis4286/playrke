<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;
    protected $table = "users";
    protected $primaryKey = 'id';
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
        'email_verified_at'
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function ReferredBy()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function Referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function UserType()
    {
        return $this->belongsTo(User::class, 'id', 'user');
    }

    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'id', 'user');
    }

    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'id', 'user');
    }

    public static function getUserTypePrefix($user)
    {
        $userType = User::getValueById($user, 'user_type');
        return UserType::getValueById($userType, 'prefix');
    }

    public static function getCreatorIDByUserType($user, $userType = '')
    {
        if (!$userType) {
            $userType = self::getUserTypePrefix($user);
        }

        $prefix = UserType::getValueById($userType, 'prefix');
        $model = ucfirst(is_string($userType) ? $userType : $prefix);

        return app("App\\Models\\$model")::where('user', $user)->value('id');
    }

    public static function getById($userId, $fields = ['*'])
    {
        return self::select($fields)->where('id', $userId)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getValueByEmail($email, $value)
    {
        return self::where('email', $email)->value($value);
    }

    public static function getUniqueReferralCode()
    {
        do {
            $code = Str::random(12);
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    public function canAccessRoom($roomId)
    {
        // Aquí debes implementar la lógica para verificar si el usuario tiene permisos para acceder a la sala con el ID proporcionado
        // Por ejemplo, puedes verificar si el usuario pertenece a la sala
        return $this->rooms->contains('id', $roomId);
    }
}

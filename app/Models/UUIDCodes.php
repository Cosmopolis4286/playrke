<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UUIDCodes extends Model
{
    protected $table = "uuid_codes";
    protected $fillable = ['code_uuid', 'user', 'user_type', 'status'];

    public function User()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public function UserType()
    {
        return $this->belongsTo(UserType::class, 'user_type');
    }

    public static function getByUser($user, $fields = ['*'])
    {
        return self::select($fields)->where('user', $user)->first();
    }

    public static function getByCode($codeUUID, $fields = ['*'])
    {
        return self::select($fields)->where('code_uuid', $codeUUID)->first();
    }

    public static function getValueByUser($user, $value)
    {
        return self::where('user', $user)->value($value);
    }

    public static function existsCode($code)
    {
        return self::where('code_uuid', $code)->exists();
    }
}

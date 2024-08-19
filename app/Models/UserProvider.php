<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProvider extends Model
{
    protected $table = "users_providers";
    protected $fillable = ['user', 'provider', 'country', 'coin', 'last_activity'];

    public static function getValueByUserId($userId, $value)
    {
        return self::where('user_id', $userId)->value('value', $value);
    }
}

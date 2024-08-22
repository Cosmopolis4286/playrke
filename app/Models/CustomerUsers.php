<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerUser extends Model
{
    protected $table = "customer_users";
    protected $fillable = ['user_id', 'customer', 'country', 'coin', 'last_activity'];

    public static function getValueByUserId($userId, $value)
    {
        return self::where('user_id', $userId)->value('value', $value);
    }
}

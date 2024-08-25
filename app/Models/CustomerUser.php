<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerUser extends Model
{
    protected $table = "customer_users";
    protected $fillable = ['customer', 'user_id', 'country', 'coin', 'last_activity'];

    public static function getValueByUserId($userId, $value)
    {
        return self::where('user_id', $userId)->value('value', $value);
    }

    public static function existsUserByCustomer($customer, $userID)
    {
        return self::where('customer', $customer)->where('user_id', $userID)->exists();
    }
}

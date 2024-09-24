<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerUser extends Model
{
    protected $table = "customer_users";
    protected $fillable = ['customer', 'user_id', 'token', 'country', 'coin', 'last_activity'];

    public static function getByToken($customer, $token, $fields = ['*'])
    {
        return self::select($fields)->Where('customer', $customer)->where('token', $token)->first();
    }

    public static function getValueByUserId($customer, $userId, $value)
    {
        return self::Where('customer', $customer)->where('user_id', $userId)->value($value);
    }

    public static function getValueByToken($customer, $token, $value)
    {
        return self::Where('customer', $customer)->where('token', $token)->value($value);
    }

    public static function existsUserByCustomer($customer, $userID)
    {
        return self::where('customer', $customer)->where('user_id', $userID)->exists();
    }
}

<?php

namespace App\Models\Users\Roles\Customer;

use Illuminate\Database\Eloquent\Model;

class CustomerUser extends Model
{
    protected $table = "customers_users";
    protected $fillable = ['customer', 'user_id', 'brand', 'token', 'country', 'currency', 'last_activity'];

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getByUserId($customer, $userId, $fields = ['*'])
    {
        return self::select($fields)->where('customer', $customer)->where('user_id', $userId)->first();
    }

    public static function getValueByUserId($customer, $userId, $value)
    {
        return self::where('customer', $customer)->where('user_id', $userId)->value($value);
    }

    public static function getByToken($customer, $token, $fields = ['*'])
    {
        return self::select($fields)->where('customer', $customer)->where('token', $token)->first();
    }

    public static function getByCustomer($customer, $token, $fields = ['*'])
    {
        return self::select($fields)->where('customer', $customer)->where('token', $token)->first();
    }

    public static function getValueByToken($customer, $token, $value)
    {
        return self::where('customer', $customer)->where('token', $token)->value($value);
    }

    public static function existsUserByCustomer($customer, $userId)
    {
        return self::where('customer', $customer)->where('user_id', $userId)->exists();
    }
}

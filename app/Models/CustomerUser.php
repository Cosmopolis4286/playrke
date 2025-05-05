<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerUser extends Model
{
    protected $table = "customers_users";
    protected $fillable = ['customer', 'user_id', 'token', 'country', 'coin', 'last_activity'];

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
        return self::select($fields)->Where('customer', $customer)->where('user_id', $userId)->first();
    }

    public static function getValueByUserId($customer, $userId, $value)
    {
        return self::Where('customer', $customer)->where('user_id', $userId)->value($value);
    }

    public static function getByToken($customer, $token, $fields = ['*'])
    {
        return self::select($fields)->Where('customer', $customer)->where('token', $token)->first();
    }

    public static function getByCustomerID($customerID, $token, $fields = ['*'])
    {
        return self::select($fields)->Where('customer_id', $customerID)
            ->where('token', $token)->first();
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

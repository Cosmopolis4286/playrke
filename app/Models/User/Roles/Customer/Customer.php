<?php

namespace App\Models\User\Roles\Customer;

use App\Models\Countries\Country;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
  protected $table = "customers";
  protected $fillable = [
    'name',
    'description',
    'country',
    'user',
    'customer_id',
    'phone',
    'commission',
    'all_in',
    'brand',
    'product',
    'logo',
    'link_route',
    'token',
    'customer_key',
    'security_key',
    'status'
  ];

  public function User()
  {
    return $this->belongsTo(User::class, 'user');
  }

  public function Country()
  {
    return $this->belongsTo(Country::class, 'country');
  }

  public static function getById($id, $fields = ['*'])
  {
    return self::select($fields)->where('id', $id)->first();
  }

  public static function getByUserId($user, $fields = ['*'])
  {
    return self::select($fields)->where('user', $user)->first();
  }

  public static function getByCustomerID($customerId, $fields = ['*'])
  {
    return self::select($fields)->where('customer_id', $customerId)->first();
  }

  public static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }

  public static function getValueByUserId($user, $value)
  {
    return self::where('user', $user)->value($value);
  }

  public static function getByToken($token, $fields = ['*'])
  {
    return self::select($fields)->where('token', $token)->first();
  }

  public static function getValueByToken($token, $value)
  {
    return self::where('token', $token)->value($value);
  }

  public static function getValueByCustomerID($customerId, $value)
  {
    return self::where('customer_id', $customerId)->value($value);
  }

  public static function existsCustomerById($id)
  {
    return self::where('id', $id)->exists();
  }

  public static function existsValue($column, $value)
  {
    return self::where($column, $value)->exists();
  }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
  protected $table = "customers";
  protected $fillable = [
    'name',
    'description',
    'country',
    'user',
    'phone',
    'commn',
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

  public function Country()
  {
    return $this->belongsTo(Country::class, 'country');
  }

  public function User()
  {
    return $this->belongsTo(User::class, 'user');
  }

  public static function getById($id, $fields = ['*'])
  {
    return self::select($fields)->where('id', $id)->first();
  }

  public static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }

  public static function getByToken($token, $fields = ['*'])
  {
    return self::select($fields)->where('token', $token)->first();
  }

  public static function getValueByToken($token, $value)
  {
    return self::where('token', $token)->value($value);
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
  protected $table = "customers";
  protected $fillable = [
    'name',
    'country',
    'phone',
    'commn',
    'all_in',
    'brand',
    'product',
    'link_route',
    'token',
    'security_key',
    'status'
  ];

  public function Country()
  {
    return $this->belongsTo(Country::class, 'country');
  }

  public static function getById($id, $fields = ['*'])
  {
    return self::select($fields)->where('id', $id)->first();
  }

  public static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }
}

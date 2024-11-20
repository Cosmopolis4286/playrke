<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commerce extends Model
{
  protected $table = "commerces";
  protected $fillable = [
    'name',
    'country',
    'coin',
    'phone',
    'user',
    'commn',
    'code_rke',
    'code_uuid',
    'status'
  ];

  public function Country()
  {
    return $this->belongsTo(Country::class, 'country');
  }

  public function Sellers()
  {
    return $this->hasMany(Seller::class, 'commerce', 'id');
  }

  public function User()
  {
    return $this->belongsTo(User::class, 'user');
  }

  public static function getByUserId($user, $fields = ['*'])
  {
    return self::select($fields)->where('user', $user)->first();
  }

  public static function getById($id, $fields = ['*'])
  {
    return self::select($fields)->where('id', $id)->first();
  }

  public static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }

  public static function getValueByUserId($user, $value)
  {
    return self::where('user', $user)->value($value);
  }

  public static function getByCodeRKE($code, $fields = ['*'])
  {
    return self::select($fields)->where('code_rke', $code)->first();
  }

  public static function getValueByCodeRKE($code, $value)
  {
    return self::where('code_rke', $code)->value($value);
  }

  public static function getByCodeUUID($code, $fields = ['*'])
  {
    return self::select($fields)->where('code_uuid', $code)->first();
  }

  public static function getValueByCodeUUID($code, $value)
  {
    return self::where('code_uuid', $code)->value($value);
  }

  public static function existsValue($colum, $value)
  {
    return self::where($colum, $value)->exists();
  }
}

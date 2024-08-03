<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commerce extends Model
{
  protected $table = "commerces";
  protected $fillable = ['name', 'country', 'coin', 'phone', 'commn', 'all_in', 'user', 'status'];

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
}

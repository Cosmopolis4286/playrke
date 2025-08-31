<?php

namespace App\Models\User\Roles\Treasurer;

use App\Models\Countries\Country;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class Treasurer extends Model
{
  protected $table = "treasurers";
  protected $fillable = [
    'name',
    'country',
    'coin',
    'phone',
    'user',
    'code_uuid',
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

  public static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }

  public static function getByUserId($user, $fields = ['*'])
  {
    return self::select($fields)->where('user', $user)->first();
  }

  public static function getValueByUserId($user, $value)
  {
    return self::where('user', $user)->value($value);
  }
}

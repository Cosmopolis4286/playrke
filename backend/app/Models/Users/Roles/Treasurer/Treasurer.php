<?php

namespace App\Models\Users\Treasurer;

use App\Models\Country;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para tesoreros del sistema.
 */
class Treasurer extends Model
{
    use SnowflakeId;

    protected $table = "treasurers";

    protected $fillable = [
        'name',
        'country',
        'currency',
        'phone',
        'user',
        'uuid_code',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
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

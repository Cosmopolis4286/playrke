<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaTypes extends Model
{
  protected $table = "polla_types";
  protected $fillable = ['name', 'hour_a', 'hour_b', 'lotteries', 'status'];

  public static function getById($id, $fields = ['*'])
  {
    return self::select($fields)->where('id', $id)->first();
  }

  public static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }
}

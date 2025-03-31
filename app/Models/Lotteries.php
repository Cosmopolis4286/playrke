<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lotteries extends Model
{
  protected $table = "lotteries";
  protected $fillable = ['name', 'code', 'numbers', 'status'];

  static function getById($id, $fields = ['*'])
  {
    return self::select($fields)->where('id', $id)->first();
  }

  static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }
}

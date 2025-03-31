<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaTypes extends Model
{
  protected $table = "polla_types";
  protected $fillable = ['name', 'hour_a', 'hour_b', 'status'];

  public static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }
}

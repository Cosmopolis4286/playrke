<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaTypesPrizes extends Model
{
  protected $table = "polla_types_prizes";
  protected $fillable = ['description', 'pct', 'status'];

  public static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }
}

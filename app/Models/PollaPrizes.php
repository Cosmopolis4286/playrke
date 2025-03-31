<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaPrizes extends Model
{
  protected $table = "polla_prizes";
  protected $fillable = ['draw', 'prize_id', 'amount'];

  public function Draw()
  {
    return $this->belongsTo(PollaDraw::class, 'draw');
  }

  public function Prize()
  {
    return $this->belongsTo(PollaTypesPrizes::class, 'prize_id');
  }

  public static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }

  public static function getByDraw($draw, $fields = ['*'])
  {
    return self::select($fields)->where('id', $draw)->first();
  }
}

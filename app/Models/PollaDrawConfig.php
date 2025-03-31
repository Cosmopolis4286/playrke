<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaDrawConfig extends Model
{
  protected $table = "polla_draw_config";
  protected $fillable = ['draw', 'game_mode', 'active_prizes'];

  public function Draw()
  {
    return $this->belongsTo(PollaDraw::class, 'draw');
  }

  public function GameMode()
  {
    return $this->belongsTo(GameModes::class, 'game_mode');
  }

  static function getById($id, $fields = ['*'])
  {
    return self::select($fields)->where('id', $id)->first();
  }

  static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }

  static function getByDraw($draw, $fields = ['*'])
  {
    return self::select($fields)->where('draw', $draw)->first();
  }

  static function getValueByDraw($draw, $value)
  {
    return self::where('draw', $draw)->value($value);
  }
}

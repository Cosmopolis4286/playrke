<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaDrawConfig extends Model
{
  protected $table = "polla_draws_config";
  protected $fillable = [
    'draw',
    'creation',
    'creator_user',
    'game_mode',
    'unix_date',
    'ticket_price',
    'jackpot_type',
    'fixed_jackpot_value',
    'active_prizes',
    'room_commn'
  ];

  public function Draw()
  {
    return $this->belongsTo(PollaDraw::class, 'draw');
  }

  public function GameMode()
  {
    return $this->belongsTo(GameModes::class, 'game_mode');
  }

  public function JackpotType()
  {
    return $this->belongsTo(JackpotType::class, 'jackpot_type');
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

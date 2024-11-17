<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaDrawConfig extends Model
{
  protected $table = "polla_draw_config";
  protected $fillable = [
    'room',
    'jackpot_type',
    'fixed_jackpot_value',
    'replay',
    'room_commn',
    'serials_commn',
    'val_serials',
    'num_serials',
    'val_sm_prizes',
    'types_plays'
  ];

  public function Room()
  {
    return $this->belongsTo(BingoRoom::class, 'room');
  }

  public function JackpotType()
  {
    return $this->belongsTo(JackpotType::class, 'jackpot_type');
  }

  static function getValueByRoom($room, $value)
  {
    return self::where('room', $room)->value($value);
  }

  static function getRoomConfig($room, $fields = ['*'])
  {
    return self::select($fields)->where('room', $room)->first();
  }
}

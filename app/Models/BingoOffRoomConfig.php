<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoOffRoomConfig extends Model
{
  protected $table = "bingo_off_rooms_config";
  protected $fillable = ['room', 'jackpot_type', 'fixed_jackpot_value', 'replay', 'commerce_commn', 'val_serials', 'num_serials', 'val_sm_prizes'];

  public function Room()
  {
    return $this->belongsTo(BingoOffRoom::class, 'room');
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
    return self::where('room', $room)->select($fields)->first();
  }
}

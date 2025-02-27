<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoomConfig extends Model
{
  protected $table = "bingo_rooms_config";
  protected $fillable = [
    'room',
    'creation',
    'game_mode',
    'date',
    'time',
    'unix_date',
    'country',
    'coin',
    'ctn_price',
    'room_commn',
    'jackpot_type',
    'fixed_jackpot_value',
    'time_chip',
    'pct_serials',
    'num_serials',
    'serials_commn',
    'val_types_plays',
    'types_plays'
  ];

  public function Room()
  {
    return $this->belongsTo(BingoRoom::class, 'room');
  }
  
  public function GameMode()
  {
    return $this->belongsTo(GameModes::class, 'game_mode');
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

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
    'room_type',
    'ctn_price',
    'jackpot_type',
    'fixed_jackpot_value',
    'replay',
    'room_commn',
    'serials_commn',
    'val_serials',
    'num_serials',
    'val_sm_prizes',
    'play_types'
  ];

  public function Creator()
  {
    return $this->belongsTo(BingoCreator::class, 'room', 'room');
  }
  
  public function Room()
  {
    return $this->belongsTo(BingoRoom::class, 'room');
  }

  public function GameMode()
  {
    return $this->belongsTo(GameModes::class, 'game_mode', 'id');
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoomConfig extends Model
{
  protected $table = "bingo_rooms_config";
  protected $fillable = [
    'room',
    'creation',
    'creator_user',
    'game_mode',
    'date',
    'time',
    'unix_date',
    'country',
    'coin',
    'ctn_price',
    'rke_commn',
    'room_commn',
    'type_prize_pool',
    'amount_added_prize_pool',
    'time_chip',
    'pct_serials',
    'num_serials',
    'pct_jackpot',
    'pct_types_plays',
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

  public function TypePrizePool()
  {
    return $this->belongsTo(TypePrizePool::class, 'type_prize_pool');
  }

  static function getValueByRoom($room, $value)
  {
    return self::where('room', $room)->value($value);
  }

  static function getByRoom($room, $fields = ['*'])
  {
    return self::select($fields)->where('room', $room)->first();
  }
}

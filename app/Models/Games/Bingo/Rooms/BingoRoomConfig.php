<?php

namespace App\Models\Games\Bingo\Rooms;

use App\Models\Games\Shared\GameMode\GameModes;
use App\Models\Games\Shared\PrizePool\PrizePoolType;
use Illuminate\Database\Eloquent\Model;

class BingoRoomConfig extends Model
{
  protected $table = "bingo_rooms_config";
  protected $fillable = [
    'room',
    'creation',
    'creator_user_id',
    'game_mode',
    'in_live',
    'date',
    'time',
    'unix_date',
    'card_price',
    'allowed_currencies',
    'rke_commn',
    'room_commn',
    'prize_pool_type',
    'amount_added_prize_pool',
    'time_chip',
    'rounds',
    'rounds_pct',
    'serials_pct',
    'num_serials',
    'jackpot_pct',
    'plays_pct',
    'play_types'
  ];

  protected $casts = ['allowed_currencies' => 'array'];

  public function Room()
  {
    return $this->belongsTo(BingoRoom::class, 'room');
  }

  public function GameMode()
  {
    return $this->belongsTo(GameModes::class, 'game_mode');
  }

  public function PrizePoolType()
  {
    return $this->belongsTo(PrizePoolType::class, 'prize_pool_type');
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

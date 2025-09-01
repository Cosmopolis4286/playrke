<?php

namespace App\Models\Games\Bingo\Rooms;

use App\Models\Games\Shared\GameMode\GameModes;
use App\Models\Games\Shared\PrizePool\PrizePoolType;
use Illuminate\Database\Eloquent\Model;

class BingoRoomAutomatic extends Model
{
  protected $table = "automatic_bingo_rooms";
  protected $fillable = [
    'description',
    'date',
    'days',
    'time',
    'game_mode',
    'in_live',
    'prize_pool_type',
    'amount_added_prize_pool',
    'room_type',
    'rke_commn',
    'room_commn',
    'ctn_price',
    'allowed_coins',
    'time_chip',
    'rounds',
    'rounds_pct',
    'serials_pct',
    'num_serials',
    'jackpot_pct',
    'plays_pct',
    'play_types',
    'creator_type',
    'creator_id',
    'creator_user_id',
    'status'
  ];

  protected $casts = ['allowed_coins' => 'array'];

  public function GameMode()
  {
    return $this->belongsTo(GameModes::class, 'game_mode?id');
  }

  public function PrizePoolType()
  {
    return $this->belongsTo(PrizePoolType::class, 'prize_pool_type');
  }

  public static function getById($id, $fields = ['*'])
  {
    return self::select($fields)->where('id', $id)->first();
  }

  public static function getByCreator($creatorType, $creatorID, $fields = ['*'])
  {
    return self::select($fields)->where($creatorType, $creatorID)->first();
  }

  public static function getValue($id, $value)
  {
    return self::where('id', $id)->value($value);
  }
}

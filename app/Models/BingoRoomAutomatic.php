<?php

namespace App\Models;

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
    'type_prize_pool',
    'amount_added_prize_pool',
    'room_type',
    'rke_commn',
    'room_commn',
    'ctn_price',
    'time_chip',
    'pct_serials',
    'num_serials',
    'pct_jackpot',
    'creator_type',
    'creator_id',
    'creator_user',
    'status'
  ];

  public function GameMode()
  {
    return $this->belongsTo(GameModes::class, 'game_mode');
  }

  public function TypePrizePool()
  {
    return $this->belongsTo(TypePrizePool::class, 'type_prize_pool');
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

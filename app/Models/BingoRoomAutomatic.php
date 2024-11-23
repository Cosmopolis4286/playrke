<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoomAutomatic extends Model
{
  protected $table = "automatic_bingo_rooms";
  protected $fillable = [
    'description',
    'date',
    'time',
    'game_mode',
    'jackpot_type',
    'fixed_jackpot_value',
    'jackpot',
    'ctn_price',
    'room_commn',
    'serials_commn',
    'val_serials',
    'num_serials',
    'creator_type',
    'creator_id',
    'status'
  ];

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

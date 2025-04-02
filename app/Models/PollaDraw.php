<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaDraw extends Model
{
  protected $table = "polla_draws";
  protected $fillable = [
    'date',
    'game_type',
    'status',
    'room_type',
    'creator_user',
    'paid_win',
    'publication_date',
    'players',
    'plays',
    'close_sale',
    'all_sale',
    'rke_commn',
    'creator_commn',
    'seller_commn',
    'jackpot',
    'code_uuid'
  ];

  public function Status()
  {
    return $this->belongsTo(StatusGame::class, 'status');
  }

  public function Config()
  {
    return $this->belongsTo(PollaDrawConfig::class, 'id', 'draw');
  }

  public function Players()
  {
    return $this->hasMany(BingoRoomUser::class, 'draw', 'id');
  }

  public function ComSeller()
  {
    return $this->belongsTo(SellerRoomLimits::class, 'id', 'room');
  }

  public function GameExchangeRate()
  {
    return $this->hasMany(GameExchangeRate::class, 'room', 'id');
  }

  public static function getById($id, $fields = ['*'])
  {
    return self::select($fields)->where('id', $id)->first();
  }

  public static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }

  public static function getByCodeUUID($codeUUID, $fields = ['*'])
  {
    return self::select($fields)->where('code_uuid', $codeUUID)->first();
  }

  public static function getValueByCodeUUID($codeUUID, $value)
  {
    return self::where('code_uuid', $codeUUID)->value($value);
  }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoom extends Model
{
  protected $table = "bingo_rooms";
  protected $fillable = [
    'encrypted',
    'game_mode',
    'customer',
    'commerce',
    'subsidiary',
    'status',
    'creation',
    'room_type',
    'paid_win',
    'date',
    'time',
    'unix_date',
    'coin',
    'publication_date',
    'ctn_price',
    'players',
    'all_sale',
    'room_commn',
    'seller_commn',
    'jackpot',
    'jackpot_prize',
    'serial_prize',
    'ctns',
    'close_room',
    'code_uuid'
  ];

  public function GameMode()
  {
    return $this->belongsTo(GameModes::class, 'game_mode', 'id');
  }

  public function Status()
  {
    return $this->belongsTo(StatusGame::class, 'status');
  }

  public function Config()
  {
    return $this->belongsTo(BingoRoomConfig::class, 'id', 'room');
  }

  public function Creator()
  {
    return $this->hasMany(BingoCreator::class, 'room', 'id');
  }

  public function Prizes()
  {
    return $this->belongsTo(BingoRoomPrizes::class, 'id', 'room');
  }

  public function Coin()
  {
    return $this->belongsTo(Coin::class, 'coin');
  }

  public function Customer()
  {
    return $this->belongsTo(Customer::class, 'customer');
  }

  public function Commerce()
  {
    return $this->belongsTo(Commerce::class, 'commerce');
  }

  public function CtnsLot()
  {
    return $this->hasMany(BingoCtnsLot::class, 'room', 'id');
  }

  public function PlayersRoom()
  {
    return $this->hasMany(BingoRoomUser::class, 'room', 'id');
  }

  public function ComSeller()
  {
    return $this->belongsTo(SellerRoomLimits::class, 'id', 'room');
  }

  public function GameExchangeRate()
  {
    return $this->hasMany(GameExchangeRate::class, 'room', 'id');
  }

  public static function getRoomById($room, $fields = ['*'])
  {
    return self::where('id', $room)->select($fields)->first();
  }

  public static function getRoomByEncrypted($encryptedID, $fields = ['*'])
  {
    return self::select($fields)->where('encrypted', $encryptedID)->first();
  }

  public static function getValueById($room, $value)
  {
    return self::where('id', $room)->value($value);
  }

  public static function getValueByEncrypted($room, $value)
  {
    return self::where('encrypted', $room)->value($value);
  }

  public static function existsRoom($room)
  {
    return self::where('id', $room)->exists();
  }

  public static function getSerialPrize($room)
  {
    return self::where('id', $room)->value('serial_prize');
  }
}

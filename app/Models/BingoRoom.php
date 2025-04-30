<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoom extends Model
{
  protected $table = "bingo_rooms";
  protected $fillable = [
    'encrypted',
    'status',
    'room_type',
    'creator_user',
    'paid_win',
    'publication_date',
    'ctn_price',
    'players',
    'ctns',
    'all_sale',
    'room_commn',
    'seller_commn',
    'jackpot',
    'jackpot_prize',
    'serial_prize',
    'close_room',
    'code_uuid',
  ];

  protected $appends = ['room_type_description'];

  public function getRoomTypeDescriptionAttribute()
  {
    return Room::TYPES[$this->room_type] ?? '';
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

  public function CtnPrice()
  {
    return $this->hasMany(BingoCtnPrice::class, 'room', 'id');
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

  public static function getById($room, $fields = ['*'])
  {
    return self::select($fields)->where('id', $room)->first();
  }

  public static function getByEncrypted($encryptedID, $fields = ['*'])
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

  public static function getValueByBingoCreator($bingoCreator, $value = 'id')
  {
    return self::where('user', $bingoCreator)->value($value);
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

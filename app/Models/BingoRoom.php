<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoRoom extends Model
{
  protected $table = "bingo_rooms";
  protected $fillable = [
    'encrypted',
    'status',
    'creation',
    'bingo_creator',
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
    'code_uuid'
  ];

  protected $appends = ['room_type_description'];

  public function getRoomTypeDescriptionAttribute()
  {
    $roomType = collect(Room::TYPES)->firstWhere('id', $this->room_type);
    return $roomType['description'] ?? 'Desconocido';
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
    return self::where('id', $room)->select($fields)->first();
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
    return self::where('bingo_creator', $bingoCreator)->value($value);
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

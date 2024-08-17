<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerLink extends Model
{
  protected $table = "player_link";
  protected $fillable = [
    'game', 'room', 'provider', 'commerce', 'seller', 'link',
    'country', 'coin', 'name', 'lastname', 'identification', 'phone'
  ];

  public function Country()
  {
    return $this->belongsTo(Country::class, 'country');
  }

  public function Coin()
  {
    return $this->belongsTo(Coin::class, 'coin');
  }

  public function GameExchangeRate()
  {
    return $this->hasMany(GameExchangeRate::class, 'room', 'room');
  }

  public function Commerce()
  {
    return $this->belongsTo(Commerce::class, 'commerce');
  }

  public function Seller()
  {
    return $this->belongsTo(Seller::class, 'seller');
  }

  public function Room()
  {
    return $this->belongsTo(BingoRoom::class, 'room');
  }

  public function PlayerGame()
  {
    return $this->belongsTo(BingoRoomUser::class, 'id', 'player_link');
  }

  public function Ctns()
  {
    return $this->belongsTo(BingoCtns::class, 'id', 'player_link');
  }

  public function WinCtns()
  {
    return $this->hasMany(BingoPrizeCtns::class, 'player_link', 'id');
  }

  public function CtnsLot()
  {
    return $this->hasMany(BingoCtnsLot::class, 'player_link', 'id');
  }

  public static function getById($id, $fields = ['*'])
  {
    return self::select($fields)->where('id', $id)->first();
  }

  public static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }

  public static function getByLink($PJLink, $fields = ['*'])
  {
    return self::where('link', $PJLink)->select($fields)->first();
  }
}

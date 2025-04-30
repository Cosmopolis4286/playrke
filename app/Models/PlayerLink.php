<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerLink extends Model
{
  protected $table = "player_link";
  protected $fillable = [
    'game',
    'room',
    'creator_user',
    'seller',
    'user',
    'link',
    'country',
    'coin',
    'name',
    'lastname',
    'identification',
    'phone',
    'purchase_code'
  ];

  public function Game()
  {
    return $this->belongsTo(Games::class, 'game');
  }

  public function Room()
  {
    return $this->belongsTo(BingoRoom::class, 'room');
  }

  public function CreatorUser()
  {
    return $this->belongsTo(User::class, 'creator_user');
  }

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

  public function PlayerGame()
  {
    return $this->belongsTo(BingoRoomUser::class, 'id', 'player_link');
  }

  public function Ctns()
  {
    return $this->belongsTo(BingoCtns::class, 'id', 'player_link');
  }

  public function PrizeCtns()
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

  public static function getValueById($id, $value = 'id')
  {
    return self::where('id', $id)->value($value);
  }

  public static function getByLink($playerLink, $fields = ['*'])
  {
    return self::select($fields)->where('link', $playerLink)->first();
  }

  public static function getValueByLink($link, $value)
  {
    return self::where('link', $link)->value($value);
  }
}

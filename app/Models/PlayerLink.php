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
    'room_user',
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

  public static function getByRoomUser($game, $roomUser, $fields = ['*'])
  {
    $gameID = Games::getValueByName($game);
    return self::select($fields)->where('game', $gameID)->where('room_user', $roomUser)->first();
  }

  public static function getValueByRoomUser($game, $roomUser, $value)
  {
    $gameID = Games::getValueByName($game);
    return self::where('game', $gameID)->where('room_user', $roomUser)->value($value);
  }
}

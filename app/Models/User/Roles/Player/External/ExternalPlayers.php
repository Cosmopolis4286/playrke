<?php

namespace App\Models\User\Roles\Player\External;

use App\Models\Currencies\Currencys\Coin;
use App\Models\Countries\Country;
use App\Models\Games\Shared\Games;
use App\Models\User\Roles\Commerce\Commerce;
use App\Models\User\Roles\Seller\Seller;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class ExternalPlayers extends Model
{
  protected $table = "external_players";
  protected $fillable = [
    'game_id',
    'room_id',
    'creator_user_id',
    'seller_id',
    'room_user',
    'link',
    'country_id',
    'currency_id',
    'name',
    'lastname',
    'identification',
    'phone',
    'purchase_code'
  ];

  public function Game()
  {
    return $this->belongsTo(Games::class, 'game_id');
  }

  public function CreatorUser()
  {
    return $this->belongsTo(User::class, 'creator_user_id');
  }

  public function Country()
  {
    return $this->belongsTo(Country::class, 'country');
  }

  public function Coin()
  {
    return $this->belongsTo(Currency::class, 'currency_id');
  }

  public function Commerce()
  {
    return $this->belongsTo(Commerce::class, 'commerce_id');
  }

  public function Seller()
  {
    return $this->belongsTo(Seller::class, 'seller_id');
  }

  public function CtnsLot()
  {
    return $this->hasMany(BingoCtnsLot::class, 'room_user', 'room_user');
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

<?php

namespace App\Models\Users\Roles\Treasurer;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;

class OtherTreasurers extends Model
{
  protected $table = "other_treasurers";
  protected $fillable = ['creator_user_id', 'allowed_games', 'allowed_currencies', 'status'];

  public function User()
  {
    return $this->belongsTo(User::class, 'creator_user_id');
  }

  public static function getById($id, $fields = ['*'])
  {
    return self::select($fields)->where('id', $id)->first();
  }

  public static function getValueById($id, $value)
  {
    return self::where('id', $id)->value($value);
  }

  public static function getByCreatorUser($creatorUser, $fields = ['*'])
  {
    return self::select($fields)->where('creator_user_id', $creatorUser)->first();
  }

  public static function getValueByCreatorUser($creatorUser, $value)
  {
    return self::where('creator_user_id', $creatorUser)->value($value);
  }

  public static function existsByCreatorUser($creatorUser)
  {
    return self::where('creator_user_id', $creatorUser)->exists();
  }

  public static function getAllCreatorUser()
  {
    return self::distinct()->pluck('creator_user_id');
  }
}

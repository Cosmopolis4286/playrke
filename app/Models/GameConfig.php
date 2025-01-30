<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameConfig extends Model
{
    protected $table = "game_config";
    protected $fillable = ['game', 'param', 'param_val', 'description', 'status', 'created_at', 'updated_at'];

    public static function getGameParam($game, $param)
    {
        $gameID = Games::getValueByName($game);
        return self::where('game', $gameID)->where('param', $param)->first();
    }

    public static function getGameParamValue($game, $param, $value = 'param_val')
    {
        $gameID = Games::getValueByName($game);
        return self::where('game', $gameID)->where('param', $param)->value($value);
    }
}

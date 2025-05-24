<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameConfig extends Model
{
    protected $table = "game_config";
    protected $fillable = ['game', 'param', 'param_value', 'description', 'status', 'created_at', 'updated_at'];

    public static function getGameParam($game, $param)
    {
        $gameID = Games::getValueByName($game);
        return self::where('game', $gameID)->where('param', $param)->first();
    }

    public static function getGameParamValue($game, $param, $value = 'param_value')
    {
        $gameID = Games::getValueByName($game);
        return self::where('game', $gameID)->where('param', $param)->value($value);
    }
}

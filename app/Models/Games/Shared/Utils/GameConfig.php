<?php

namespace App\Models\Games\Shared\Utils;

use App\Models\Games\Shared\Games;
use Illuminate\Database\Eloquent\Model;

class GameConfig extends Model
{
    protected $table = "game_config";
    protected $fillable = ['game_id', 'param', 'param_value', 'description', 'status', 'created_at', 'updated_at'];

    public static function getGameParam($gameId, $param)
    {
        $gameId = Games::getValueByName($gameId);
        return self::where('game_id', $gameId)->where('param', $param)->first();
    }

    public static function getGameParamValue($game, $param, $value = 'param_value')
    {
        $gameID = Games::getValueByName($game);
        return self::where('game', $gameId)->where('param', $param)->value($value);
    }
}

<?php

namespace App\Models\Games\Shared\Utils;

use App\Models\Games\Shared\Games;
use Illuminate\Database\Eloquent\Model;

class GamePrizes extends Model
{
    protected $table = "game_prizes";
    protected $fillable = ['description', 'code', 'game_id', 'status'];

    public function Game()
    {
        return $this->hasMany(Games::class, 'game_id');
    }

    public static function getValueByDescription($game, $description, $value = 'id')
    {
        $gameId = Games::getValueByName($game);
        return self::where('game', $gameId)->where('description', $description)->value($value);
    }

    public static function getValueByCode($game, $code, $value = 'id')
    {
        $gameId = Games::getValueByName($game);
        return self::where('game', $gameId)->where('code', $code)->value($value);
    }

    public static function pluckGamePrizes($game, $value, $index = 'id')
    {
        $gameId = Games::getValueByName($game);
        return self::where('game', $gameId)->pluck($value, $index);
    }
}

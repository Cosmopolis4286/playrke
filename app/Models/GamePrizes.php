<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePrizes extends Model
{
    protected $table = "game_prizes";
    protected $fillable = ['description', 'code', 'game', 'status'];

    public function Game()
    {
        return $this->hasMany(Games::class, 'game');
    }

    public static function getValueByDescription($game, $description, $value = 'id')
    {
        $gameID = Games::getValueByName($game);
        return self::where('game', $gameID)->where('description', $description)->value($value);
    }

    public static function getAllGamePrize($game)
    {
        $gameID = Games::getValueByName($game);
        return self::where('game', $gameID)->pluck('code', 'id');
    }
}

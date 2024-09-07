<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameModes extends Model
{
    protected $table = "game_mode_relationship";
    protected $fillable = ['id', 'game', 'game_mode', 'status'];

    public static function getGameModes($game, $status = 1)
    {
        return self::where('game', $game)->where('status', $status)->get(['id', 'description']);
    }
}

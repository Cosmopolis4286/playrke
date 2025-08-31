<?php

namespace App\Models\Games\Shared\GameMode;

use Illuminate\Database\Eloquent\Model;

class GameModes extends Model
{
    protected $table = "game_mode_relationship";
    protected $fillable = ['game_id', 'game_mode', 'status'];

    public static function getGameModes($gameId, $status = 1)
    {
        return self::where('game_id', $gameId)->where('status', $status)->get(['id', 'description']);
    }
}

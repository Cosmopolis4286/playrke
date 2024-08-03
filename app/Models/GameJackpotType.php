<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameJackpotType extends Model
{
    protected $table = "game_jackpot_type";
    protected $fillable = ['game', 'game_mode', 'jackpot_type', 'status'];

    public function Game()
    {
        return $this->belongsTo(Games::class, 'game');
    }

    public function GameMode()
    {
        return $this->belongsTo(GameModes::class, 'game_mode');
    }

    public function JackpotType()
    {
        return $this->belongsTo(JackpotType::class, 'jackpot_type');
    }

    public static function getAvailableByGameMode($game, $game_mode = '')
    {
        return self::where('game', $game)->where('game_mode', $game_mode)
            ->where('status', 1)->pluck('jackpot_type')->toArray();
    }
}

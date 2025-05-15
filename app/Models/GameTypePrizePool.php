<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameTypePrizePool extends Model
{
    protected $table = "game_type_prize_pool";
    protected $fillable = ['game', 'game_mode', 'type_prize_pool', 'status'];

    public function Game()
    {
        return $this->belongsTo(Games::class, 'game');
    }

    public function GameMode()
    {
        return $this->belongsTo(GameModes::class, 'game_mode');
    }

    public function TypePrizePool()
    {
        return $this->belongsTo(TypePrizePool::class, 'type_prize_pool');
    }

    public static function getAvailableByGameMode($game, $game_mode = '')
    {
        return self::select('type_prize_pool')->where('game', $game)->where('game_mode', $game_mode)
            ->where('status', 1)->distinct()->pluck('type_prize_pool')->toArray();
    }
}

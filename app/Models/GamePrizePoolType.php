<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePrizePoolType extends Model
{
    protected $table = "game_prize_pool_type";
    protected $fillable = ['game', 'game_mode', 'prize_pool_type', 'status'];

    public function Game()
    {
        return $this->belongsTo(Games::class, 'game');
    }

    public function GameMode()
    {
        return $this->belongsTo(GameModes::class, 'game_mode');
    }

    public function PrizePoolType()
    {
        return $this->belongsTo(PrizePoolType::class, 'prize_pool_type');
    }

    public static function getAvailableByGameMode($game, $game_mode = '')
    {
        return self::select('prize_pool_type')->where('game', $game)->where('game_mode', $game_mode)
            ->where('status', 1)->distinct()->pluck('prize_pool_type')->toArray();
    }
}

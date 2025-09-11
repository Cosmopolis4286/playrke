<?php

namespace App\Models\Games\Shared\PrizePool;

use App\Models\Games\Shared\GameMode\GameMode;
use App\Models\Games\Shared\Game;
use Illuminate\Database\Eloquent\Model;

class GamePrizePoolType extends Model
{
    protected $table = 'game_prize_pool_types';

    protected $fillable = [
        'game_id',
        'game_mode_id',
        'prize_pool_type_id',
        'status',
    ];

    /* -------------------------
     | Relaciones
     |--------------------------*/

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function gameMode()
    {
        return $this->belongsTo(GameMode::class, 'game_mode_id');
    }

    public function prizePoolType()
    {
        return $this->belongsTo(PrizePoolType::class, 'prize_pool_type_id');
    }
}

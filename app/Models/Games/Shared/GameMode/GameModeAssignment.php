<?php

namespace App\Models\Games\Shared\GameMode;

use App\Models\Games\Shared\Game;
use Illuminate\Database\Eloquent\Model;
use App\Models\Games\Shared\GameMode\GameMode;

class GameModeAssignment extends Model
{
    protected $table = 'game_mode_assignment'; 
    
    protected $fillable = [
        'game_id',
        'game_mode_id',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean', // si status es 1/0
    ];

    /**
     * Relación con Game
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * Relación con GameMode
     */
    public function gameMode()
    {
        return $this->belongsTo(GameMode::class, 'game_mode_id');
    }
}

<?php

namespace App\Models\Shared\GameMode;

use App\Models\Game;
use App\Models\Games\Shared\GameMode\GameMode;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameModeAssignment extends Model
{
    use SnowflakeId;

    protected $table = 'game_mode_assignment';

    protected $fillable = [
        'game_id',
        'game_mode_id',
        'status',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function gameMode(): BelongsTo
    {
        return $this->belongsTo(GameMode::class, 'game_mode_id');
    }
}

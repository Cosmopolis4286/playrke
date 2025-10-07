<?php

namespace App\Models\Games\Shared\PrizePool;

use App\Models\Games\Shared\GameMode\GameMode;
use App\Models\Games\Shared\Game;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePrizePoolType extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'game_id',
        'game_mode_id',
        'prize_pool_type_id',
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

    public function prizePoolType(): BelongsTo
    {
        return $this->belongsTo(PrizePoolType::class, 'prize_pool_type_id');
    }
}

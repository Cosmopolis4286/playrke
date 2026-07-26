<?php

namespace App\Models\Games\Common\PrizePool;

use App\Models\Games\Common\ConnectionType\ConnectionType;
use App\Models\Game;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePrizePoolType extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'game_id',
        'connection_type_id',
        'prize_pool_type_id',
        'status',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function connectionType(): BelongsTo
    {
        return $this->belongsTo(ConnectionType::class, 'connection_type_id');
    }

    public function prizePoolType(): BelongsTo
    {
        return $this->belongsTo(PrizePoolType::class, 'prize_pool_type_id');
    }
}

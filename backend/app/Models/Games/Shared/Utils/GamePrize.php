<?php

namespace App\Models\Games\Shared\Utils;

use App\Models\Games\Game;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePrize extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'description',
        'code',
        'game_id',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}

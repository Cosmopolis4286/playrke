<?php

namespace App\Models\Games\Common\Utils;

use App\Casts\AsActiveStatusEnum;
use App\Models\Game;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePrize extends Model
{
    use SnowflakeId;

    protected $casts = [
        'status' => AsActiveStatusEnum::class,
    ];

    protected $fillable = [
        'description',
        'code',
        'game_id',
        'status',
    ];

    public function game() : BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}

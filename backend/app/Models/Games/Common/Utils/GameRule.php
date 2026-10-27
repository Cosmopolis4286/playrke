<?php

namespace App\Models\Games\Common\Utils;

use App\Casts\AsActiveStatusEnum;
use App\Models\Game;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRule extends Model
{
    use SnowflakeId;

    protected $casts = [
        'status' => AsActiveStatusEnum::class,
    ];

    protected $fillable = [
        'user_id',
        'game_id',
        'image',
        'rules',
        'encrypted',
        'status',
    ];

    public function game() : BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

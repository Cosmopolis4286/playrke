<?php

namespace App\Models\Games\Shared\Utils;

use App\Models\Games\Game;
use App\Models\Users\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRule extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'user_id',
        'game_id',
        'image',
        'rules',
        'encrypted',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}

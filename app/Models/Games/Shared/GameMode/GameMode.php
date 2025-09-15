<?php

namespace App\Models\Games\Shared\GameMode;

use App\Models\Games\Shared\PrizePool\GamePrizePoolType;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMode extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function prizePoolTypes(): HasMany
    {
        return $this->hasMany(GamePrizePoolType::class, 'game_mode_id');
    }
}

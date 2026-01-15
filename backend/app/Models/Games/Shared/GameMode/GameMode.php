<?php

namespace App\Models\Shared\GameMode;

use App\Models\Shared\PrizePool\GamePrizePoolType;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMode extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function prizePoolTypes(): HasMany
    {
        return $this->hasMany(GamePrizePoolType::class, 'game_mode_id');
    }
}

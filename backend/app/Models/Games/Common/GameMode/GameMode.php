<?php

namespace App\Models\Games\Common\GameMode;

use App\Models\Games\Common\PrizePool\GamePrizePoolType;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMode extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'code',
        'label',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'code' => 'string',
        'label' => 'string',
        'status' => 'string',
    ];

    public function prizePoolTypes(): HasMany
    {
        return $this->hasMany(GamePrizePoolType::class, 'game_mode_id');
    }
}

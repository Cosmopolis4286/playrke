<?php

namespace App\Models\Games\Common\GameMode;

use App\Models\BaseModel;
use App\Models\Games\Common\PrizePool\GamePrizePoolType;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMode extends BaseModel
{

    protected $fillable = [
        'description',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'status' => 'string',
    ];

    public function prizePoolTypes(): HasMany
    {
        return $this->hasMany(GamePrizePoolType::class, 'game_mode_id');
    }
}

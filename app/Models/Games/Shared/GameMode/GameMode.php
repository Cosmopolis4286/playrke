<?php

namespace App\Models\Games\Shared\GameMode;

use App\Models\Games\Shared\PrizePool\GamePrizePoolType;
use Illuminate\Database\Eloquent\Model;

class GameMode extends Model
{
    protected $table = 'game_modes';

    // Solo los campos asignables masivamente
    protected $fillable = [
        'description',
        'is_active',
    ];

    // Casts
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relación con GamePrizePoolType
     */
    public function prizePoolTypes()
    {
        return $this->hasMany(GamePrizePoolType::class, 'game_mode_id');
    }
}

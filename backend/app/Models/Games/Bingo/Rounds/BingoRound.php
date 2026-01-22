<?php

namespace App\Models\Bingo\Rounds;

use App\Models\Bingo\Rooms\BingoRoom;
use App\Models\Games\Bingo\Cards\Patterns\BingoPattern;
use App\Models\Bingo\Rooms\User\BingoRoomWinner;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para rondas de bingo
 */
class BingoRound extends Model
{
    use SnowflakeId;

    protected $table = 'bingo_rounds';

    protected $fillable = [
        'room_id',
        'round',
        'start_time',
        'last_chip',
        'bingo_pattern_id',
        'prize_type',
        'prize_value',
        'status'
    ];

    protected $casts = [
        'round' => 'integer',
        'start_time' => 'datetime',
        'prize_value' => 'decimal:6'
    ];

    /**
     * Relación con la sala de bingo
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Relación con el patrón de bingo
     */
    public function bingoPattern(): BelongsTo
    {
        return $this->belongsTo(BingoPattern::class, 'bingo_pattern_id');
    }

    /**
     * Relación con los ganadores de esta ronda
     */
    public function winners(): HasMany
    {
        return $this->hasMany(BingoRoomWinner::class, 'round_id');
    }
}

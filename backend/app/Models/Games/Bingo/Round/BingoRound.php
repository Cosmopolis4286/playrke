<?php

namespace App\Models\Games\Bingo\Round;

use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Games\Bingo\Pattern\BingoPattern;
use App\Models\Games\Bingo\Room\User\BingoRoomWinner;
use App\Models\Prize;
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
        'prize_id',
        'status'
    ];

    protected $casts = [
        'room_id'          => 'string',
        'round'            => 'integer',
        'start_time'       => 'datetime',
        'bingo_pattern_id' => 'string',
        'prize_id'         => 'string',
        'prize_value'      => 'decimal:6',
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
     * Premio físico/servicio seleccionado (solo cuando prize_type = OTHER)
     */
    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class, 'prize_id');
    }

    /**
     * Premios fijos por moneda (solo cuando prize_type = FIXED)
     */
    public function prizes(): HasMany
    {
        return $this->hasMany(BingoRoundPrize::class, 'bingo_round_id');
    }

    /**
     * Relación con los ganadores de esta ronda
     */
    public function winners(): HasMany
    {
        return $this->hasMany(BingoRoomWinner::class, 'round_id');
    }
}

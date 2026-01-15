<?php

namespace App\Models\Bingo\Rounds;

use App\Models\Bingo\Rooms\BingoRoom;
use App\Models\Bingo\Rooms\BingoRoomAutomatic;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para drafts de rondas de bingo
 */
class BingoRoundDraft extends Model
{
    use HasFactory, SnowflakeId;

    protected $table = 'bingo_rounds_drafts';

    protected $fillable = [
        'bingo_room_id',
        'automatic_bingo_room_id',
        'rounds',
        'prize_types',
        'bingo_patterns',
        'prize_values',
        'status'
    ];

    protected $casts = [
        'prize_types' => 'array',
        'bingo_patterns' => 'array',
        'prize_values' => 'array'
    ];

    /**
     * Relación con la sala de bingo
     */
    public function bingoRoom(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'bingo_room_id');
    }

    /**
     * Relación con la sala automática de bingo
     */
    public function automaticBingoRoom(): BelongsTo
    {
        return $this->belongsTo(BingoRoomAutomatic::class, 'automatic_bingo_room_id');
    }
}
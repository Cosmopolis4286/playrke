<?php

namespace App\Models\Games\Bingo\Room;

use App\Models\Games\Common\GameMode\GameMode;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para configuraciones de salas de bingo
 */
class BingoRoomConfig extends Model
{
    use SnowflakeId;

    protected $table = 'bingo_rooms_config';

    protected $fillable = [
        'room_id',
        'creation',
        'creator_user_id',
        'game_mode_id',
        'in_live',
        'date',
        'time',
        'unix_date',
        'card_price',
        'rke_commn',
        'room_commn',
        'prize_pool_type_id',
        'amount_added_prize_pool',
        'time_chip',
        'rounds',
        'rounds_pct',
        'serials_pct',
        'num_serials',
        'jackpot_pct',
        'plays_pct'
    ];

    protected $casts = [
        'room_id' => 'string',
        'creator_user_id' => 'string',
        'game_mode_id' => 'string',
        'prize_pool_type_id' => 'string',
        'date' => 'date',
        'card_price' => 'decimal:2',
        'rke_commn' => 'decimal:2',
        'room_commn' => 'decimal:2',
        'amount_added_prize_pool' => 'decimal:2',
        'rounds_pct' => 'decimal:2',
        'serials_pct' => 'decimal:2',
        'jackpot_pct' => 'decimal:2',
        'plays_pct' => 'decimal:2',
        'unix_date' => 'integer',
        'num_serials' => 'integer'
    ];

    /**
     * Descripción del tipo de sala
     */
    public function getRoomTypeDescriptionAttribute(): string
    {
        return match ($this->room_type) {
            'PRIVATE' => 'Público',
            'PUBLIC' => 'Privado',
            default => 'Desconocido',
        };
    }

    /**
     * Relación con la sala de bingo
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Relación con el usuario creador
     */
    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Relación con el modo de juego
     */
    public function gameMode(): BelongsTo
    {
        return $this->belongsTo(GameMode::class, 'game_mode_id');
    }

    /**
     * Relación con el tipo de pool de premios
     */
    public function prizePoolType(): BelongsTo
    {
        return $this->belongsTo(PrizePoolType::class, 'prize_pool_type_id');
    }

    /**
     * Scope para salas por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('room_type', $type);
    }
}

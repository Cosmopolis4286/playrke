<?php

namespace App\Domains\Games\Bingo\Models;

use App\Models\Games\Common\GameMode\GameMode;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\Games\Common\Room\RoomType;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para configuraciones de salas de bingo - Dominio Bingo
 */
class BingoRoomConfig extends Model
{
    use SnowflakeId;

    protected $table = 'bingo_rooms_config';

    protected $fillable = [
        'room_id',
        'creation',
        'room_type_id',
        'creator_user_id',
        'game_mode_id',
        'in_live',
        'date',
        'time',
        'unix_date',
        'rke_commission',
        'room_commission',
        'use_base_usd',
        'card_price_usd',
        'usd_exchange_round_step',
        'allowed_currencies',
        'prize_pool_type_id',
        'amount_added_prize_pool',
        'time_chip',
        'rounds',
        'rounds_pct',
        'use_serials',
        'serials_pct',
        'num_serials',
        'jackpot_pct',
    ];

    protected $casts = [
        'room_id'                 => 'string',
        'creation'                => 'string',
        'room_type_id'            => 'string',
        'creator_user_id'         => 'string',
        'game_mode_id'            => 'string',
        'prize_pool_type_id'      => 'string',
        'date'                    => 'date',
        'rke_commission'          => 'decimal:2',
        'room_commission'         => 'decimal:2',
        'use_base_usd'            => 'boolean',
        'card_price_usd'          => 'decimal:2',
        'usd_exchange_round_step' => 'decimal:2',
        'allowed_currencies'      => 'array',
        'amount_added_prize_pool' => 'decimal:2',
        'rounds_pct'              => 'decimal:2',
        'use_serials'             => 'boolean',
        'serials_pct'             => 'decimal:2',
        'jackpot_pct'             => 'decimal:2',
        'unix_date'               => 'integer',
        'num_serials'             => 'integer',
    ];

    /**
     * Relación con la sala de bingo
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Relación con el tipo de sala.
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
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

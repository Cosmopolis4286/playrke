<?php

namespace App\Domains\Games\Bingo\Models\Room;

use App\Models\Games\Common\GameMode\GameMode;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\Games\Common\Room\RoomType;
use App\Models\User;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BingoRoomTemplate extends Model
{
    protected $table = 'bingo_room_templates';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'description',
        'room_type_id',
        'creator_user_id',
        'game_mode_id',
        'in_live',
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
        'id' => 'string',
        'room_type_id' => 'string',
        'creator_user_id' => 'string',
        'game_mode_id' => 'string',
        'prize_pool_type_id' => 'string',
        'in_live' => 'string',
        'use_base_usd' => 'boolean',
        'card_price_usd' => 'decimal:2',
        'usd_exchange_round_step' => 'decimal:2',
        'allowed_currencies' => 'array',
        'amount_added_prize_pool' => 'decimal:2',
        'rounds_pct' => 'decimal:2',
        'use_serials' => 'boolean',
        'serials_pct' => 'decimal:2',
        'jackpot_pct' => 'decimal:2',
    ];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function gameMode(): BelongsTo
    {
        return $this->belongsTo(GameMode::class, 'game_mode_id');
    }

    public function prizePoolType(): BelongsTo
    {
        return $this->belongsTo(PrizePoolType::class, 'prize_pool_type_id');
    }

    public function configs(): HasMany
    {
        return $this->hasMany(BingoRoomConfig::class, 'template_id', 'id');
    }

    public function automaticRooms(): HasMany
    {
        return $this->hasMany(BingoRoomAutomatic::class, 'template_id', 'id');
    }
}

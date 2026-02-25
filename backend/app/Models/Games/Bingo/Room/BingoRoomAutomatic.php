<?php

namespace App\Models\Games\Bingo\Room;

use App\Models\Games\Bingo\Card\CardPrice\BingoCardPriceAutomatic;
use App\Models\Games\Bingo\Round\BingoRoundAutomatic;
use App\Models\Games\Common\GameMode\GameMode;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BingoRoomAutomatic extends Model
{
    use SnowflakeId, SoftDeletes;

    protected $table = 'bingo_rooms_automatic';

    // Status enum values (uppercase, as in migration)
    public const STATUS_ACTIVE   = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    public const STATUSES = [
        'ACTIVE'   => 'Activa',
        'INACTIVE' => 'Inactiva',
    ];

    public const DAYS = [
        'monday'    => 'Lunes',
        'tuesday'   => 'Martes',
        'wednesday' => 'Miércoles',
        'thursday'  => 'Jueves',
        'friday'    => 'Viernes',
        'saturday'  => 'Sábado',
        'sunday'    => 'Domingo',
    ];

    protected $fillable = [
        'date',
        'days',
        'time',
        'in_live',
        'game_mode_id',
        'room_type_id',
        'rke_commission',
        'room_commission',
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
        'creator_user_id',
        'status',
    ];

    protected $casts = [
        'id'                      => 'string',
        'game_mode_id'            => 'string',
        'room_type_id'            => 'string',
        'prize_pool_type_id'      => 'string',
        'creator_user_id'         => 'string',
        'allowed_currencies'      => 'array',
        'days'                    => 'array',
        'in_live'                 => 'string',
        'date'                    => 'date',
        'time'                    => 'datetime:H:i:s',
        'card_price_usd'          => 'decimal:2',
        'usd_exchange_round_step' => 'decimal:2',
        'rke_commission'          => 'decimal:2',
        'room_commission'         => 'decimal:2',
        'amount_added_prize_pool' => 'decimal:2',
        'rounds_pct'              => 'decimal:2',
        'serials_pct'             => 'decimal:2',
        'jackpot_pct'             => 'decimal:2',
        'time_chip'               => 'string',
        'rounds'                  => 'integer',
        'num_serials'             => 'integer',
        'created_at'              => 'datetime',
        'updated_at'              => 'datetime',
    ];

    protected $appends = [
        'formatted_time',
        'days_description',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function cardPriceAutomatic(): HasOne
    {
        return $this->hasOne(BingoCardPriceAutomatic::class, 'automatic_bingo_room_id');
    }

    public function roundAutomatic(): HasOne
    {
        return $this->hasOne(BingoRoundAutomatic::class, 'automatic_bingo_room_id');
    }

    public function gameMode(): BelongsTo
    {
        return $this->belongsTo(GameMode::class, 'game_mode_id');
    }

    public function prizePoolType(): BelongsTo
    {
        return $this->belongsTo(PrizePoolType::class, 'prize_pool_type_id');
    }

    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByCreator($query, string $creatorType, string $creatorId)
    {
        return $query->where('creator_type', $creatorType)
                     ->where('creator_id', $creatorId);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getFormattedTimeAttribute(): string
    {
        return $this->time ? $this->time->format('H:i') : '';
    }

    public function getDaysDescriptionAttribute(): string
    {
        if (!$this->days || !is_array($this->days)) {
            return '';
        }
        return implode(', ', array_map(fn($d) => self::DAYS[$d] ?? $d, $this->days));
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}

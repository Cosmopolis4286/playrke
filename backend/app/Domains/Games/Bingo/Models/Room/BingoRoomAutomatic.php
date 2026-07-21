<?php

namespace App\Domains\Games\Bingo\Models\Room;

use App\Models\Games\Common\GameMode\GameMode;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\Concerns\HasHashedRouteKey;
use App\Models\Games\Common\Room\RoomType;
use App\Models\User;
use App\Domains\Games\Bingo\Models\Room\BingoRoomTemplate;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BingoRoomAutomatic extends Model
{
    use SnowflakeId, SoftDeletes, HasHashedRouteKey;

    protected $table = 'bingo_rooms_automatic';

    public const STATUS_ACTIVE   = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';

    public const STATUSES = [
        'ACTIVE'   => 'Activa',
        'INACTIVE' => 'Inactiva',
        'SUSPENDED' => 'Suspendida',
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

    public const RECURRENCE_DAILY = 'DAILY';
    public const RECURRENCE_WEEKLY = 'WEEKLY';
    public const RECURRENCE_MONTHLY = 'MONTHLY';
    public const RECURRENCE_CUSTOM = 'CUSTOM';

    public const RECURRENCE_TYPES = [
        'DAILY'   => 'Diario',
        'WEEKLY'  => 'Semanal',
        'MONTHLY' => 'Mensual',
        'CUSTOM'  => 'Personalizado',
    ];

    protected $fillable = [
        'template_id',
        'in_live',
        'game_mode_id',
        'room_type_id',
        'rke_commission',
        'room_commission',
        'use_base_usd',
        'card_price_usd',
        'usd_exchange_round_step',
        'allowed_currencies',
        'rooms_per_currency',
        'prize_pool_type_id',
        'amount_added_prize_pool',
        'time_chip',
        'rounds',
        'rounds_pct',
        'use_serials',
        'serials_pct',
        'num_serials',
        'jackpot_pct',
        'min_cards_to_start',
        'allow_custom_card',
        'custom_card_price_pct',
        'custom_card_mode',
        'custom_card_every_x',
        'allow_move_free_space',
        'allow_special_card',
        'max_special_cards',
        'sales_start_offset_days',
        'creator_user_id',
        'status',
        // Nuevos campos de programación avanzada
        'recurrence_type',
        'recurrence_days',
        'game_time',
        'start_date',
        'end_date',
        'default_config',
        'default_card_price',
        'default_currency_id',
        'auto_create_room',
        'max_rooms_per_day',
        'rooms_created',
        'last_room_created_at',
        // Campos consolidados de rondas automáticas
        'rounds_config',
        'prize_types',
        'bingo_patterns',
        'prize_values',
        // Campos consolidados de precios automáticos
        'card_currencies',
        'card_prices',
    ];

    protected $casts = [
        'id'                      => 'string',
        'template_id'             => 'string',
        'game_mode_id'            => 'string',
        'room_type_id'            => 'string',
        'prize_pool_type_id'      => 'string',
        'creator_user_id'         => 'string',
        'default_currency_id'     => 'string',
        'allowed_currencies'      => 'array',
        'rooms_per_currency'      => 'boolean',
        'recurrence_days'         => 'array',
        'default_config'          => 'array',
        'rounds_config'           => 'array',
        'prize_types'             => 'array',
        'bingo_patterns'          => 'array',
        'prize_values'            => 'array',
        'card_currencies'         => 'array',
        'card_prices'             => 'array',
        'in_live'                 => 'string',
        'game_time'               => 'datetime:H:i:s',
        'start_date'              => 'date',
        'end_date'                => 'date',
        'use_base_usd'            => 'boolean',
        'card_price_usd'          => 'decimal:2',
        'default_card_price'      => 'decimal:2',
        'usd_exchange_round_step' => 'decimal:2',
        'rke_commission'          => 'decimal:2',
        'room_commission'         => 'decimal:2',
        'amount_added_prize_pool' => 'decimal:2',
        'rounds_pct'              => 'decimal:2',
        'serials_pct'             => 'decimal:2',
        'jackpot_pct'             => 'decimal:2',
        'min_cards_to_start'      => 'integer',
        'allow_custom_card'       => 'boolean',
        'custom_card_price_pct'   => 'decimal:2',
        'custom_card_mode'        => 'string',
        'custom_card_every_x'     => 'integer',
        'allow_move_free_space'   => 'boolean',
        'allow_special_card'      => 'boolean',
        'max_special_cards'       => 'integer',
        'sales_start_offset_days' => 'integer',
        'time_chip'               => 'string',
        'use_serials'             => 'boolean',
        'auto_create_room'        => 'boolean',
        'rounds'                  => 'integer',
        'num_serials'             => 'integer',
        'max_rooms_per_day'       => 'integer',
        'rooms_created'           => 'integer',
        'created_at'              => 'datetime',
        'updated_at'              => 'datetime',
        'last_room_created_at'    => 'datetime',
    ];

    protected $appends = [
        'formatted_time',
        'days_description',
        'status_badge',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function generatedRoomConfigs(): HasMany
    {
        return $this->hasMany(BingoRoomConfig::class, 'parent_automatic_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(BingoRoomTemplate::class, 'template_id');
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

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'default_currency_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByCreator($query, string $creatorUserId)
    {
        return $query->where('creator_user_id', $creatorUserId);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getFormattedTimeAttribute(): string
    {
        return $this->game_time ? $this->game_time->format('H:i') : '';
    }

    public function getDaysDescriptionAttribute(): string
    {
        if (!$this->recurrence_days || !is_array($this->recurrence_days)) {
            return '';
        }
        $dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 0 => 'Domingo'];
        return implode(', ', array_map(fn($d) => $dayNames[$d] ?? $d, $this->recurrence_days));
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'ACTIVE'   => '<span class="badge badge-success">Activa</span>',
            'INACTIVE' => '<span class="badge badge-secondary">Inactiva</span>',
            'SUSPENDED' => '<span class="badge badge-warning">Suspendida</span>',
            default    => '<span class="badge badge-dark">Desconocido</span>',
        };
    }

    // ─── Consolidated Rounds & Prices Helpers ────────────────────────────────

    /**
     * Obtiene la configuración de rondas consolidada.
     */
    public function getRoundsConfig(): array
    {
        return $this->rounds_config && is_array($this->rounds_config)
            ? $this->rounds_config
            : [];
    }

    /**
     * Obtiene los precios de cartones consolidados.
     */
    public function getCardPricesConfig(): array
    {
        $hasData = is_array($this->card_currencies) && is_array($this->card_prices)
            && (count($this->card_currencies) > 0 || count($this->card_prices) > 0);

        return $hasData
            ? ['currencies' => $this->card_currencies, 'card_prices' => $this->card_prices]
            : ['currencies' => [], 'card_prices' => []];
    }
}

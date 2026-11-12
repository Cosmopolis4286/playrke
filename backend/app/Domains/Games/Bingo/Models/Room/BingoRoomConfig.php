<?php

namespace App\Domains\Games\Bingo\Models\Room;

use App\Models\Games\Common\ConnectionType\ConnectionType;
use App\Models\Games\Common\GameMode\GameMode;
use App\Models\Games\Common\Jackpot\JackpotType;
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

    protected $casts = [
        'room_id' => 'string',
        'template_id' => 'string',
        'parent_automatic_id' => 'string',
        'is_scheduled' => 'string',
        'start_mode' => 'string',
        'from_house' => 'string',
        'room_type_id' => 'string',
        'access_code' => 'string',
        'room_password' => 'string',
        'host_user_id' => 'string',
        'creator_user_id' => 'string',
        'connection_type_id' => 'string',
        'game_mode_id' => 'string',
        'game_variant' => 'string',
        'prize_pool_type_id' => 'string',
        'date' => 'date',
        'rke_commission' => 'decimal:2',
        'room_commission' => 'decimal:2',
        'use_base_usd' => 'boolean',
        'card_price_usd' => 'decimal:2',
        'usd_exchange_round_step' => 'decimal:2',
        'allowed_currencies' => 'array',
        'rooms_per_currency_split' => 'boolean',
        'amount_added_prize_pool' => 'decimal:2',
        'rounds_pct' => 'decimal:2',
        'use_serials' => 'boolean',
        'serials_pct' => 'decimal:2',
        'jackpot_pct' => 'decimal:2',
        'jackpot_type_id' => 'string',
        'unix_date' => 'integer',
        'num_serials' => 'integer',
        'time_chip' => 'integer',
        'round_pause_seconds' => 'integer',
        'min_cards_to_start' => 'integer',
        'max_cards_to_start' => 'integer',
        'max_players' => 'integer',
        'vip_min_level' => 'integer',
        'allow_network_sales' => 'boolean',
        'vip_min_spend_usd' => 'decimal:2',
        'min_players' => 'integer',
        'allow_custom_card' => 'boolean',
        'custom_card_price_pct' => 'decimal:2',
        'custom_card_mode' => 'string',
        'custom_card_every_x' => 'integer',
        'move_free_space_mode' => 'string',
        'move_free_space_price_pct' => 'decimal:2',
        'allow_special_card' => 'boolean',
        'max_special_cards' => 'integer',
    ];

    protected $fillable = [
        'room_id',
        'template_id',
        'parent_automatic_id',
        'is_scheduled',
        'start_mode',
        'min_cards_to_start',
        'max_cards_to_start',
        'max_players',
        'vip_min_level',
        'allow_network_sales',
        'vip_min_spend_usd',
        'min_players',
        'from_house',
        'room_type_id',
        'access_code',
        'room_password',
        'host_user_id',
        'creator_user_id',
        'connection_type_id',
        'game_mode_id',
        'game_variant',
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
        'rooms_per_currency_split',
        'prize_pool_type_id',
        'amount_added_prize_pool',
        'time_chip',
        'round_pause_seconds',
        'rounds',
        'rounds_pct',
        'use_serials',
        'serials_pct',
        'num_serials',
        'jackpot_pct',
        'jackpot_type_id',
        'allow_custom_card',
        'custom_card_price_pct',
        'custom_card_mode',
        'custom_card_every_x',
        'move_free_space_mode',
        'move_free_space_price_pct',
        'allow_special_card',
        'max_special_cards',
    ];

    protected $hidden = [
        'room_password',
    ];

    protected $table = 'bingo_rooms_config';

    /**
     * Relación con el tipo de conexión
     */
    public function connectionType() : BelongsTo
    {
        return $this->belongsTo(ConnectionType::class, 'connection_type_id');
    }

    /**
     * Relación con el usuario creador
     */
    public function creatorUser() : BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Relación con el modo de juego (catálogo game_modes, opcional).
     */
    public function gameMode() : BelongsTo
    {
        return $this->belongsTo(GameMode::class, 'game_mode_id');
    }

    /**
     * Relación con el usuario anfitrión (solo aplica a salas privadas).
     */
    public function hostUser() : BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    /**
     * Relación con el tipo de jackpot al que aporta el % configurado en jackpot_pct.
     */
    public function jackpotType() : BelongsTo
    {
        return $this->belongsTo(JackpotType::class, 'jackpot_type_id');
    }

    /**
     * Plantilla automatica que origino esta configuracion, cuando aplica.
     */
    public function parentAutomatic() : BelongsTo
    {
        return $this->belongsTo(BingoRoomAutomatic::class, 'parent_automatic_id');
    }

    /**
     * Relación con el tipo de pool de premios
     */
    public function prizePoolType() : BelongsTo
    {
        return $this->belongsTo(PrizePoolType::class, 'prize_pool_type_id');
    }

    /**
     * Relación con la sala de bingo
     */
    public function room() : BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Relación con el tipo de sala.
     */
    public function roomType() : BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    /**
     * Scope para salas por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('room_type', $type);
    }

    /**
     * Salas que inician por horario programado.
     */
    public function scopeScheduled($query)
    {
        return $query->where('start_mode', 'SCHEDULED');
    }

    /**
     * Salas que requieren activación manual del anfitrión/admin (sin horario).
     */
    public function scopeUnscheduled($query)
    {
        return $query->where('start_mode', 'MANUAL');
    }

    public function template() : BelongsTo
    {
        return $this->belongsTo(BingoRoomTemplate::class, 'template_id');
    }
}

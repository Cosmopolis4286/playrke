<?php

namespace App\Models\Games\Bingo\Cards\CardPrize;

use App\Models\Currency;
use App\Models\Games\Bingo\Cards\BingoCard;
use App\Models\Games\Bingo\Cards\BingoCardLot;
use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models\Games\Bingo\Rooms\User\BingoRoomUser;
use App\Models\Roles\Seller;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para los premios de cartones de bingo
 *
 * Representa un premio otorgado a un cartón ganador en una sala de bingo.
 * Incluye información sobre el tipo de premio, montos, estado y patrón ganador.
 *
 * @package App\Models\Bingo\Cards
 * @author Sistema PlayRKE
 * @version 1.0
 *
 * @property int $id ID único del premio
 * @property string $room_id ID de la sala de bingo
 * @property string $creator_user_id ID del usuario creador
 * @property string|null $seller_id ID del vendedor (opcional)
 * @property string $room_user_id ID del usuario en la sala
 * @property string $card_lot_id ID del lote de cartones
 * @property string $winning_card_id ID del cartón ganador
 * @property string $currency_id ID de la moneda utilizada
 * @property string $game_round Ronda del juego
 * @property bool $is_active Estado de activación del premio
 * @property string $prize_type Tipo de premio
 * @property string $winning_pattern Patrón del cartón ganador
 * @property float $usd_amount Monto en USD
 * @property float $local_amount Monto en moneda local
 * @property string $prize_status Estado del premio
 * @property \Carbon\Carbon|null $claimed_at Fecha de reclamo
 * @property string|null $verification_code Código de verificación
 * @property string|null $prize_notes Notas del premio
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación suave
 *
 * @property-read BingoRoom $room Sala de bingo asociada
 * @property-read User $creatorUser Usuario creador del premio
 * @property-read Seller|null $seller Vendedor asociado
 * @property-read BingoRoomUser $roomUser Usuario en la sala
 * @property-read BingoCardLot $cardLot Lote de cartones
 * @property-read BingoCard $winningCard Cartón ganador
 * @property-read Currency $currency Moneda utilizada
 * @property-read \Illuminate\Database\Eloquent\Collection|BingoCardPrizePayment[] $payments Pagos del premio
 */
class BingoCardPrize extends Model
{
    use SnowflakeId, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'bingo_card_prizes';

    /**
     * Tipos de premio disponibles
     */
    public const PRIZE_TYPES = [
        'full_house' => 'Casa Llena',
        'single_line' => 'Línea Simple',
        'serial' => 'Serial',
    ];

    /**
     * Estados de premio disponibles
     */
    public const PRIZE_STATUSES = [
        'waiting' => 'Esperando',
        'claimed' => 'Reclamado',
        'cancelled' => 'Cancelado',
        'suspended' => 'Suspendido',
    ];

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'room_id',
        'creator_user_id',
        'seller_id',
        'room_user_id',
        'card_lot_id',
        'winning_card_id',
        'currency_id',
        'game_round',
        'status',
        'prize_type',
        'winning_pattern',
        'usd_amount',
        'local_amount',
        'prize_status',
        'claimed_at',
        'verification_code',
        'prize_notes',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'status' => 'boolean',
        'usd_amount' => 'decimal:2',
        'local_amount' => 'decimal:8',
        'claimed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Campos que se agregan automáticamente al modelo
     */
    protected $appends = ['prize_type_description', 'prize_status_description'];

    /**
     * Relación con la sala de bingo
     *
     * @return BelongsTo<BingoRoom, BingoCardPrize>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Relación con el usuario creador del premio
     *
     * @return BelongsTo<User, BingoCardPrize>
     */
    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Relación con el vendedor (opcional)
     *
     * @return BelongsTo<Seller, BingoCardPrize>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Relación con el usuario en la sala
     *
     * @return BelongsTo<BingoRoomUser, BingoCardPrize>
     */
    public function roomUser(): BelongsTo
    {
        return $this->belongsTo(BingoRoomUser::class, 'room_user_id');
    }

    /**
     * Relación con el lote de cartones
     *
     * @return BelongsTo<BingoCardLot, BingoCardPrize>
     */
    public function cardLot(): BelongsTo
    {
        return $this->belongsTo(BingoCardLot::class, 'card_lot_id');
    }

    /**
     * Relación con el cartón ganador
     *
     * @return BelongsTo<BingoCard, BingoCardPrize>
     */
    public function winningCard(): BelongsTo
    {
        return $this->belongsTo(BingoCard::class, 'winning_card_id');
    }

    /**
     * Relación con la moneda utilizada
     *
     * @return BelongsTo<Currency, BingoCardPrize>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relación con los pagos del premio
     *
     * @return HasMany<BingoCardPrizePayment>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(BingoCardPrizePayment::class, 'card_prize_id');
    }

    /**
     * Obtener la descripción del tipo de premio
     *
     * @return string
     */
    public function getPrizeTypeDescriptionAttribute(): string
    {
        return self::PRIZE_TYPES[$this->prize_type] ?? $this->prize_type;
    }

    /**
     * Obtener la descripción del estado del premio
     *
     * @return string
     */
    public function getPrizeStatusDescriptionAttribute(): string
    {
        return self::PRIZE_STATUSES[$this->prize_status] ?? $this->prize_status;
    }

    /**
     * Verificar si el premio está esperando ser reclamado
     *
     * @return bool
     */
    public function isWaiting(): bool
    {
        return $this->prize_status === 'waiting';
    }

    /**
     * Verificar si el premio ha sido reclamado
     *
     * @return bool
     */
    public function isClaimed(): bool
    {
        return $this->prize_status === 'claimed';
    }

    /**
     * Verificar si el premio está activo
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status;
    }

    /**
     * Marcar el premio como reclamado
     *
     * @return bool
     */
    public function markAsClaimed(): bool
    {
        $this->prize_status = 'claimed';
        $this->claimed_at = now();

        return $this->save();
    }

    /**
     * Activar el premio
     *
     * @return bool
     */
    public function activate(): bool
    {
        $this->status = true;
        return $this->save();
    }

    /**
     * Desactivar el premio
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        $this->status = false;
        return $this->save();
    }

    /**
     * Generar código de verificación único
     *
     * @return string
     */
    public function generateVerificationCode(): string
    {
        $this->verification_code = strtoupper(substr(md5(uniqid()), 0, 8));
        $this->save();

        return $this->verification_code;
    }

    /**
     * Verifica si el premio ha sido cancelado
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->prize_status === 'cancelled';
    }

    /**
     * Verifica si el premio está suspendido
     *
     * @return bool
     */
    public function isSuspended(): bool
    {
        return $this->prize_status === 'suspended';
    }

    /**
     * Obtiene el valor formateado en USD
     *
     * @return string
     */
    public function getFormattedUsdAmount(): string
    {
        return number_format($this->usd_amount, 2);
    }

    /**
     * Obtiene el valor formateado en moneda local
     *
     * @return string
     */
    public function getFormattedLocalAmount(): string
    {
        return number_format($this->local_amount, 8);
    }

    /**
     * Scope para obtener solo premios activos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope para obtener solo premios inactivos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }

    /**
     * Scope para obtener premios por estado
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('prize_status', $status);
    }

    /**
     * Scope para obtener premios por tipo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('prize_type', $type);
    }

    /**
     * Scope para obtener premios esperando ser reclamados
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWaiting($query)
    {
        return $query->where('prize_status', 'waiting');
    }

    /**
     * Scope para obtener premios reclamados
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClaimed($query)
    {
        return $query->where('prize_status', 'claimed');
    }

    /**
     * Scope para obtener premios cancelados
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCancelled($query)
    {
        return $query->where('prize_status', 'cancelled');
    }

    /**
     * Scope para obtener premios suspendidos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuspended($query)
    {
        return $query->where('prize_status', 'suspended');
    }

    /**
     * Scope para obtener premios por sala
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $roomId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRoom($query, string $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    /**
     * Scope para obtener premios por creador
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $creatorUserId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCreator($query, string $creatorUserId)
    {
        return $query->where('creator_user_id', $creatorUserId);
    }

    /**
     * Scope para obtener premios por ronda
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $gameRound
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGameRound($query, string $gameRound)
    {
        return $query->where('game_round', $gameRound);
    }

    /**
     * Scope para obtener premios con código de verificación
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithVerificationCode($query)
    {
        return $query->whereNotNull('verification_code');
    }

    /**
     * Scope para obtener premios sin código de verificación
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutVerificationCode($query)
    {
        return $query->whereNull('verification_code');
    }
}

<?php

namespace App\Models\Games\Bingo\Cards;

use App\Models\Currency;
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
 * Modelo para los lotes de cartones de bingo
 *
 * Representa un lote de cartones comprados por un jugador en una sala de bingo.
 * Cada lote contiene información sobre la cantidad de cartones, precios, comisiones
 * y está asociado a una sala específica y un jugador.
 *
 * @package App\Models\Bingo\Cards
 * @author Sistema PlayRKE
 * @version 1.0
 *
 * @property int $id ID único del lote
 * @property string $room_id ID de la sala de bingo
 * @property string $creator_user_id ID del usuario creador
 * @property string|null $seller_id ID del vendedor (opcional)
 * @property string $room_user_id ID del usuario en la sala
 * @property string $currency_id ID de la moneda utilizada
 * @property int $card_count Cantidad de cartones en el lote
 * @property float $card_price Precio unitario por cartón
 * @property float $exchange_rate Tipo de cambio aplicado
 * @property float $sale_amount Monto total de la venta
 * @property float|null $commission_amount Comisión del vendedor
 * @property bool $is_active Estado del lote (activo/inactivo)
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación suave
 *
 * @property-read BingoRoom $room Sala de bingo asociada
 * @property-read User $creatorUser Usuario creador del lote
 * @property-read Seller|null $seller Vendedor asociado
 * @property-read BingoRoomUser $roomUser Usuario en la sala
 * @property-read Currency $currency Moneda utilizada
 * @property-read \Illuminate\Database\Eloquent\Collection|BingoCard[] $cards Cartones del lote
 * @property-read \Illuminate\Database\Eloquent\Collection|BingoCardPayment[] $payments Pagos del lote
 */
class BingoCardLot extends Model
{
    use SnowflakeId, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'bingo_card_lots';

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'room_id',
        'creator_user_id',
        'seller_id',
        'room_user_id',
        'currency_id',
        'card_count',
        'card_price',
        'exchange_rate',
        'sale_amount',
        'commission_amount',
        'status',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'card_count' => 'integer',
        'card_price' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'sale_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con la sala de bingo
     *
     * @return BelongsTo<BingoRoom, BingoCardLot>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Relación con el usuario creador del lote
     *
     * @return BelongsTo<User, BingoCardLot>
     */
    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Relación con el vendedor (opcional)
     *
     * @return BelongsTo<Seller, BingoCardLot>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Relación con el usuario en la sala
     *
     * @return BelongsTo<BingoRoomUser, BingoCardLot>
     */
    public function roomUser(): BelongsTo
    {
        return $this->belongsTo(BingoRoomUser::class, 'room_user_id');
    }

    /**
     * Relación con la moneda utilizada
     *
     * @return BelongsTo<Currency, BingoCardLot>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relación con los cartones del lote
     *
     * @return HasMany<BingoCard>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(BingoCard::class, 'card_lot_id');
    }

    /**
     * Relación con los pagos del lote
     *
     * @return HasMany<BingoCardPayment>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(BingoCardPayment::class, 'card_lot_id');
    }


    /**
     * Calcular el monto total incluyendo comisiones
     *
     * @return float
     */
    public function getTotalAmountWithCommission(): float
    {
        return $this->sale_amount + ($this->commission_amount ?? 0);
    }


    /**
     * Scope para obtener solo lotes activos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope para obtener lotes por vendedor
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sellerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySeller($query, string $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }
}

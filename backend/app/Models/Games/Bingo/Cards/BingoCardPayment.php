<?php

declare(strict_types=1);

namespace App\Models\Games\Bingo\Cards;

use App\Models\Currencies\Currency;
use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models\Users\Player\External\ExternalPlayers;
use App\Models\Users\Seller\Seller;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para los pagos de cartones de bingo
 *
 * Representa un pago realizado por la compra de cartones de bingo.
 * Incluye información sobre el método de pago, monto, comprobante y estado.
 *
 * @package App\Models\Games\Bingo\Cards
 * @author Sistema PlayRKE
 * @version 1.0
 *
 * @property int $id ID único del pago
 * @property string $payment_method Método de pago utilizado
 * @property string|null $description Descripción opcional del pago
 * @property string $room_id ID de la sala de bingo
 * @property string $creator_user_id ID del usuario creador
 * @property string|null $seller_id ID del vendedor (opcional)
 * @property string|null $user_id ID del usuario que realizó el pago
 * @property string|null $external_player_id ID del jugador externo
 * @property string $currency_id ID de la moneda utilizada
 * @property string $card_lot_id ID del lote de cartones
 * @property float $payment_amount Monto del pago
 * @property string $payment_proof Comprobante del pago
 * @property string $payment_status Estado del pago
 * @property \Carbon\Carbon|null $processed_at Fecha de procesamiento
 * @property string|null $transaction_reference Referencia de transacción
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación suave
 *
 * @property-read BingoRoom $room Sala de bingo asociada
 * @property-read User $creatorUser Usuario creador del pago
 * @property-read Seller|null $seller Vendedor asociado
 * @property-read User|null $user Usuario que realizó el pago
 * @property-read ExternalPlayers|null $externalPlayer Jugador externo
 * @property-read Currency $currency Moneda utilizada
 * @property-read BingoCardLot $cardLot Lote de cartones asociado
 */
class BingoCardPayment extends Model
{
    use SnowflakeId, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'bingo_card_payments';

    /**
     * Métodos de pago disponibles
     */
    public const PAYMENT_METHODS = [
        'payment_system' => 'Sistema de Pago',
        'seller_cash' => 'Efectivo del Vendedor',
    ];

    /**
     * Estados de pago disponibles
     */
    public const PAYMENT_STATUSES = [
        'pending' => 'Pendiente',
        'completed' => 'Completado',
        'failed' => 'Fallido',
        'cancelled' => 'Cancelado',
    ];

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'payment_method',
        'description',
        'room_id',
        'creator_user_id',
        'seller_id',
        'user_id',
        'external_player_id',
        'currency_id',
        'card_lot_id',
        'payment_amount',
        'payment_proof',
        'payment_status',
        'processed_at',
        'transaction_reference',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_status' => 'string',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con la sala de bingo
     *
     * @return BelongsTo<BingoRoom, BingoCardPayment>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Relación con el usuario creador del pago
     *
     * @return BelongsTo<User, BingoCardPayment>
     */
    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Relación con el vendedor (opcional)
     *
     * @return BelongsTo<Seller, BingoCardPayment>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Relación con el usuario que realizó el pago
     *
     * @return BelongsTo<User, BingoCardPayment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con el jugador externo
     *
     * @return BelongsTo<ExternalPlayers, BingoCardPayment>
     */
    public function externalPlayer(): BelongsTo
    {
        return $this->belongsTo(ExternalPlayers::class, 'external_player_id');
    }

    /**
     * Relación con la moneda utilizada
     *
     * @return BelongsTo<Currency, BingoCardPayment>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relación con el lote de cartones
     *
     * @return BelongsTo<BingoCardLot, BingoCardPayment>
     */
    public function cardLot(): BelongsTo
    {
        return $this->belongsTo(BingoCardLot::class, 'card_lot_id');
    }

    /**
     * Obtener la descripción del método de pago
     *
     * @return string
     */
    public function getPaymentMethodDescription(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Obtener la descripción del estado del pago
     *
     * @return string
     */
    public function getPaymentStatusDescription(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    /**
     * Verificar si el pago está completado
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->payment_status === 'completed';
    }

    /**
     * Verificar si el pago está pendiente
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }



    /**
     * Scope para obtener solo pagos completados
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('payment_status', 'completed');
    }

    /**
     * Scope para obtener solo pagos pendientes
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    /**
     * Scope para obtener pagos por método
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $method
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }
}

<?php

namespace App\Models\Games\Bingo\Cards\CardPrize;

use App\Models\Currencies\Currency;
use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models\Users\Roles\Player\External\ExternalPlayers;
use App\Models\Users\Roles\Seller\Seller;
use App\Models\Users\User;
use App\Traits\HasSnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para los pagos de premios de cartones de bingo
 * 
 * Representa un pago realizado por un premio ganado en bingo.
 * Incluye información sobre el método de pago, monto, comprobante y estado del pago del premio.
 * 
 * @package App\Models\Games\Bingo\Cards
 * @author Sistema PlayRKE
 * @version 1.0
 * 
 * @property int $id ID único del pago de premio
 * @property string $card_prize_id ID del premio del cartón
 * @property string $payment_method Método de pago utilizado
 * @property string $room_id ID de la sala de bingo
 * @property string $creator_user_id ID del usuario creador
 * @property string|null $seller_id ID del vendedor (opcional)
 * @property string|null $external_player_id ID del jugador externo
 * @property string|null $user_id ID del usuario que recibe el pago
 * @property string $currency_id ID de la moneda utilizada
 * @property float $payment_amount Monto del pago del premio
 * @property string $payment_proof Comprobante del pago
 * @property string $payment_status Estado del pago
 * @property \Carbon\Carbon|null $processed_at Fecha de procesamiento
 * @property string|null $transaction_reference Referencia de transacción
 * @property string|null $payment_notes Notas del pago
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación suave
 * 
 * @property-read BingoCardPrize $cardPrize Premio del cartón asociado
 * @property-read BingoRoom $room Sala de bingo asociada
 * @property-read User $creatorUser Usuario creador del pago
 * @property-read Seller|null $seller Vendedor asociado
 * @property-read ExternalPlayers|null $externalPlayer Jugador externo
 * @property-read User|null $user Usuario que recibe el pago
 * @property-read Currency $currency Moneda utilizada
 */
class BingoCardPrizePayment extends Model
{
    use HasSnowflakeId, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'bingo_card_prize_payments';

    /**
     * Métodos de pago disponibles para premios
     */
    public const PAYMENT_METHODS = [
        'seller_cash' => 'Efectivo del Vendedor',
        'credit_card' => 'Tarjeta de Crédito',
        'system_payment' => 'Pago del Sistema',
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
        'card_prize_id',
        'payment_method',
        'room_id',
        'creator_user_id',
        'seller_id',
        'external_player_id',
        'user_id',
        'currency_id',
        'payment_amount',
        'payment_proof',
        'payment_status',
        'processed_at',
        'transaction_reference',
        'payment_notes',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'payment_amount' => 'decimal:8',
        'payment_status' => 'string',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Campos que se agregan automáticamente al modelo
     */
    protected $appends = ['payment_method_description', 'payment_status_description'];

    /**
     * Relación con el premio del cartón
     * 
     * @return BelongsTo<BingoCardPrize, BingoCardPrizePayment>
     */
    public function cardPrize(): BelongsTo
    {
        return $this->belongsTo(BingoCardPrize::class, 'card_prize_id');
    }

    /**
     * Relación con la sala de bingo
     * 
     * @return BelongsTo<BingoRoom, BingoCardPrizePayment>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Relación con el usuario creador del pago
     * 
     * @return BelongsTo<User, BingoCardPrizePayment>
     */
    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Relación con el vendedor (opcional)
     * 
     * @return BelongsTo<Seller, BingoCardPrizePayment>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Relación con el jugador externo
     * 
     * @return BelongsTo<ExternalPlayers, BingoCardPrizePayment>
     */
    public function externalPlayer(): BelongsTo
    {
        return $this->belongsTo(ExternalPlayers::class, 'external_player_id');
    }

    /**
     * Relación con el usuario que recibe el pago
     * 
     * @return BelongsTo<User, BingoCardPrizePayment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con la moneda utilizada
     * 
     * @return BelongsTo<Currency, BingoCardPrizePayment>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Obtener la descripción del método de pago
     * 
     * @return string
     */
    public function getPaymentMethodDescriptionAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Obtener la descripción del estado del pago
     * 
     * @return string
     */
    public function getPaymentStatusDescriptionAttribute(): string
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
     * Verificar si el pago falló
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->payment_status === 'failed';
    }

    /**
     * Verificar si el pago fue cancelado
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->payment_status === 'cancelled';
    }

    /**
     * Marcar el pago como completado
     *
     * @return bool
     */
    public function markAsCompleted(): bool
    {
        $this->payment_status = 'completed';
        $this->processed_at = now();

        return $this->save();
    }

    /**
     * Marcar el pago como fallido
     *
     * @return bool
     */
    public function markAsFailed(): bool
    {
        $this->payment_status = 'failed';
        $this->processed_at = now();

        return $this->save();
    }

    /**
     * Marcar el pago como cancelado
     *
     * @return bool
     */
    public function markAsCancelled(): bool
    {
        $this->payment_status = 'cancelled';
        $this->processed_at = now();

        return $this->save();
    }

    /**
     * Generar referencia de transacción única
     *
     * @return string
     */
    public function generateTransactionReference(): string
    {
        $this->transaction_reference = 'PRP-' . strtoupper(uniqid());
        $this->save();

        return $this->transaction_reference;
    }

    /**
     * Verifica si el pago ha sido procesado
     *
     * @return bool
     */
    public function isProcessed(): bool
    {
        return !is_null($this->processed_at);
    }

    /**
     * Obtiene el monto formateado del pago
     *
     * @return string
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->payment_amount, 8);
    }

    /**
     * Verifica si es un pago en efectivo del vendedor
     *
     * @return bool
     */
    public function isSellerCashPayment(): bool
    {
        return $this->payment_method === 'seller_cash';
    }

    /**
     * Verifica si es un pago con tarjeta de crédito
     *
     * @return bool
     */
    public function isCreditCardPayment(): bool
    {
        return $this->payment_method === 'credit_card';
    }

    /**
     * Verifica si es un pago del sistema
     *
     * @return bool
     */
    public function isSystemPayment(): bool
    {
        return $this->payment_method === 'system_payment';
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
     * Scope para obtener solo pagos fallidos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }

    /**
     * Scope para obtener solo pagos cancelados
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCancelled($query)
    {
        return $query->where('payment_status', 'cancelled');
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

    /**
     * Scope para obtener pagos por estado
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Scope para obtener pagos procesados
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    /**
     * Scope para obtener pagos sin procesar
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    /**
     * Scope para obtener pagos por vendedor
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sellerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySeller($query, string $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Scope para obtener pagos por sala
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
     * Scope para obtener pagos por premio
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $prizeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPrize($query, string $prizeId)
    {
        return $query->where('card_prize_id', $prizeId);
    }

    /**
     * Scope para obtener pagos por usuario receptor
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para obtener pagos por jugador externo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $externalPlayerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByExternalPlayer($query, string $externalPlayerId)
    {
        return $query->where('external_player_id', $externalPlayerId);
    }

    /**
     * Scope para obtener pagos en efectivo del vendedor
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSellerCash($query)
    {
        return $query->where('payment_method', 'seller_cash');
    }

    /**
     * Scope para obtener pagos con tarjeta de crédito
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreditCard($query)
    {
        return $query->where('payment_method', 'credit_card');
    }

    /**
     * Scope para obtener pagos del sistema
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystemPayment($query)
    {
        return $query->where('payment_method', 'system_payment');
    }
}
}

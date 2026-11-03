<?php

namespace App\Domains\Games\Bingo\Models\Card\CardPrize;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\BaseModel;
use App\Models\Currency;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoCardPrizePayment extends BaseModel
{
    public const PAYMENT_METHODS = [
        'seller_cash' => 'Efectivo del Vendedor',
        'credit_card' => 'Tarjeta de Crédito',
        'system_payment' => 'Pago del Sistema',
    ];

    public const PAYMENT_STATUSES = [
        'pending' => 'Pendiente',
        'completed' => 'Completado',
        'failed' => 'Fallido',
        'cancelled' => 'Cancelado',
    ];

    protected $appends = ['payment_method_description', 'payment_status_description'];

    protected $casts = [
        'payment_amount' => 'decimal:8',
        'payment_status' => 'string',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'card_prize_id', 'payment_method', 'room_id', 'creator_user_id', 'seller_user_id', 'seller_type',
        'external_player_id', 'user_id', 'currency_id', 'payment_amount', 'payment_proof',
        'payment_status', 'processed_at', 'transaction_reference', 'payment_notes',
    ];
    protected $table = 'bingo_card_prize_payments';

    public function cardPrize() : BelongsTo
    {
        return $this->belongsTo(BingoCardPrize::class, 'card_prize_id');
    }

    public function creatorUser() : BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function externalPlayer() : BelongsTo
    {
        return $this->belongsTo(ExternalPlayer::class, 'external_player_id')->withTrashed();
    }

    public function getPaymentMethodDescriptionAttribute() : string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function getPaymentStatusDescriptionAttribute() : string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    public function room() : BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    public function scopeByExternalPlayer($query, string $id)
    {
        return $query->where('external_player_id', $id);
    }

    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByPrize($query, string $id)
    {
        return $query->where('card_prize_id', $id);
    }

    public function scopeByRoom($query, string $id)
    {
        return $query->where('room_id', $id);
    }

    public function scopeBySeller($query, string $id)
    {
        return $query->where('seller_user_id', $id);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopeByUser($query, string $id)
    {
        return $query->where('user_id', $id);
    }

    public function scopeCancelled($query)
    {
        return $query->where('payment_status', 'cancelled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopeCreditCard($query)
    {
        return $query->where('payment_method', 'credit_card');
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    public function scopeSellerCash($query)
    {
        return $query->where('payment_method', 'seller_cash');
    }

    public function scopeSystemPayment($query)
    {
        return $query->where('payment_method', 'system_payment');
    }

    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    public function seller() : BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

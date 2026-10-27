<?php

declare(strict_types=1);

namespace App\Domains\Payments\Models;

use App\Domains\Marketplace\Crypto\Models\CryptoTransaction;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Orden de compra de un PaymentProduct pagada en USDT-TRC20. Ver
 * PAYMENTS_USDT_TRC20_DESIGN.md secciones 4 y 6 para el diagrama de estados
 * y las defensas contra doble-acreditación/pago insuficiente-excesivo.
 *
 * Todas las transiciones de status deben pasar por PaymentOrderService /
 * BlockchainWatcherService (lockForUpdate + UPDATE...WHERE status = X) —
 * este modelo no expone mutadores de status para no invitar a saltarse esos
 * guards de concurrencia.
 */
class PaymentOrder extends Model
{
    use SnowflakeId;
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_CONFIRMING = 'CONFIRMING';
    public const STATUS_CREDITED = 'CREDITED';
    public const STATUS_DETECTED = 'DETECTED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_OVERPAID = 'OVERPAID';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_UNDERPAID = 'UNDERPAID';

    /** Estados en los que la orden ya no puede recibir más transiciones. */
    public const TERMINAL_STATUSES = [
        self::STATUS_EXPIRED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
        self::STATUS_CREDITED,
    ];
    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'payment_product_id' => 'string',
        'derivation_index' => 'integer',
        'crypto_transaction_id' => 'string',
        'expected_amount' => 'float',
        'received_amount' => 'float',
        'detected_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $fillable = [
        'user_id',
        'payment_product_id',
        'network',
        'token',
        'token_contract',
        'derivation_index',
        'deposit_address',
        'expected_amount',
        'received_amount',
        'crypto_transaction_id',
        'status',
        'detected_at',
        'confirmed_at',
        'expires_at',
        'metadata',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'payment_orders';

    public function attempts() : HasMany
    {
        return $this->hasMany(PaymentAttempt::class, 'payment_order_id');
    }

    public function cryptoTransaction() : BelongsTo
    {
        return $this->belongsTo(CryptoTransaction::class, 'crypto_transaction_id');
    }

    public function isExpired() : bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isTerminal() : bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function paymentProduct() : BelongsTo
    {
        return $this->belongsTo(PaymentProduct::class, 'payment_product_id');
    }

    public function purchase() : HasOne
    {
        return $this->hasOne(Purchase::class, 'payment_order_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', self::TERMINAL_STATUSES);
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

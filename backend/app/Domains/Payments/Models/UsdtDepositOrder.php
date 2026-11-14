<?php

declare(strict_types=1);

namespace App\Domains\Payments\Models;

use App\Domains\Wallet\Models\Wallet;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Orden de depósito USDT-TRC20 a la billetera (una dirección + monto único).
 * Las transiciones de estado pasan por UsdtDepositService/UsdtDepositWatcher
 * con UPDATE condicionado al estado previo, nunca asignando status a mano.
 *
 * PENDING → CONFIRMING (pago detectado) → CREDITED
 * PENDING → EXPIRED (sin pago a tiempo)
 * PENDING|EXPIRED → MANUAL_REVIEW (pago tardío o reclamo con txid) → CREDITED|REJECTED
 */
class UsdtDepositOrder extends Model
{
    use SnowflakeId;

    /** Estados que reservan su monto único (no se reasigna mientras duren). */
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_CONFIRMING, self::STATUS_MANUAL_REVIEW];

    public const STATUS_CONFIRMING = 'CONFIRMING';
    public const STATUS_CREDITED = 'CREDITED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_MANUAL_REVIEW = 'MANUAL_REVIEW';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_REJECTED = 'REJECTED';

    public const UNITS_PER_USDT = 1_000_000;

    protected $casts = [
        'requested_amount' => 'float',
        'amount_units' => 'integer',
        'received_amount' => 'float',
        'credited_amount' => 'float',
        'expires_at' => 'datetime',
        'detected_at' => 'datetime',
        'credited_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'wallet_id',
        'requested_amount',
        'amount_units',
        'deposit_address',
        'status',
        'tx_hash',
        'from_address',
        'received_amount',
        'credited_amount',
        'wallet_movement_id',
        'review_reason',
        'reviewed_by',
        'expires_at',
        'detected_at',
        'credited_at',
        'reviewed_at',
    ];

    protected $table = 'usdt_deposit_orders';

    /** Monto exacto a pagar, con 6 decimales (ej. "10.000347"). */
    public function payAmount() : string
    {
        return number_format($this->amount_units / self::UNITS_PER_USDT, 6, '.', '');
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function wallet() : BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }
}

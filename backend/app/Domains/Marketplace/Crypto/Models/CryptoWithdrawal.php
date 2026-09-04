<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Models;

use App\Models\Currency;
use App\Models\User;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletHold;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoWithdrawal extends Model
{
    use SnowflakeId;

    protected $table = 'crypto_withdrawals';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_REQUESTED   = 'REQUESTED';
    public const STATUS_RESERVED    = 'RESERVED';
    public const STATUS_REVIEWING   = 'REVIEWING';
    public const STATUS_APPROVED    = 'APPROVED';
    public const STATUS_PROCESSING  = 'PROCESSING';
    public const STATUS_BROADCASTED = 'BROADCASTED';
    public const STATUS_CONFIRMED   = 'CONFIRMED';
    public const STATUS_COMPLETED   = 'COMPLETED';
    public const STATUS_REJECTED    = 'REJECTED';
    public const STATUS_CANCELLED   = 'CANCELLED';
    public const STATUS_FAILED      = 'FAILED';

    /** Estados finales — ninguna transición sale de aquí. */
    public const FINAL_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
        self::STATUS_FAILED,
    ];

    protected $fillable = [
        'user_id',
        'wallet_id',
        'currency_id',
        'amount',
        'destination_address',
        'network',
        'wallet_hold_id',
        'crypto_transaction_id',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'id'                     => 'string',
        'user_id'                => 'string',
        'wallet_id'              => 'string',
        'currency_id'            => 'string',
        'wallet_hold_id'         => 'string',
        'crypto_transaction_id'  => 'string',
        'reviewed_by'            => 'string',
        'amount'                 => 'float',
        'reviewed_at'            => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function walletHold(): BelongsTo
    {
        return $this->belongsTo(WalletHold::class, 'wallet_hold_id');
    }

    public function cryptoTransaction(): BelongsTo
    {
        return $this->belongsTo(CryptoTransaction::class, 'crypto_transaction_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true);
    }
}

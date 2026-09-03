<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Models;

use App\Models\User;
use App\Models\Wallet;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CryptoTransaction extends Model
{
    use SnowflakeId;

    protected $table = 'crypto_transactions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public const DIRECTION_DEPOSIT    = 'DEPOSIT';
    public const DIRECTION_WITHDRAWAL = 'WITHDRAWAL';

    public const STATUS_PENDING   = 'PENDING';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_CREDITED  = 'CREDITED';
    public const STATUS_FAILED    = 'FAILED';

    protected $fillable = [
        'tx_hash',
        'network',
        'token',
        'direction',
        'from_address',
        'to_address',
        'amount',
        'status',
        'confirmations',
        'user_id',
        'wallet_id',
        'confirmed_at',
    ];

    protected $casts = [
        'id'            => 'string',
        'user_id'       => 'string',
        'wallet_id'     => 'string',
        'amount'        => 'float',
        'confirmations' => 'integer',
        'confirmed_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function deposit(): HasOne
    {
        return $this->hasOne(CryptoDeposit::class, 'crypto_transaction_id');
    }
}

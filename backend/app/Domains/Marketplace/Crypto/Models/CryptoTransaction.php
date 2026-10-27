<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Models;

use App\Domains\Wallet\Models\Wallet;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CryptoTransaction extends Model
{
    use SnowflakeId;

    public const DIRECTION_DEPOSIT = 'DEPOSIT';
    public const DIRECTION_WITHDRAWAL = 'WITHDRAWAL';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_CREDITED = 'CREDITED';
    public const STATUS_FAILED = 'FAILED';

    public const STATUS_PENDING = 'PENDING';
    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'wallet_id' => 'string',
        'amount' => 'float',
        'confirmations' => 'integer',
        'confirmed_at' => 'datetime',
    ];

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
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'crypto_transactions';

    public function deposit() : HasOne
    {
        return $this->hasOne(CryptoDeposit::class, 'crypto_transaction_id');
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

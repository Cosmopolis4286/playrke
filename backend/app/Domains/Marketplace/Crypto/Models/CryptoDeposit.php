<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Models;

use App\Models\WalletMovement;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoDeposit extends Model
{
    use SnowflakeId;

    protected $table = 'crypto_deposits';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'crypto_transaction_id',
        'wallet_movement_id',
    ];

    protected $casts = [
        'id'                     => 'string',
        'crypto_transaction_id'  => 'string',
        'wallet_movement_id'     => 'string',
    ];

    public function cryptoTransaction(): BelongsTo
    {
        return $this->belongsTo(CryptoTransaction::class, 'crypto_transaction_id');
    }

    public function walletMovement(): BelongsTo
    {
        return $this->belongsTo(WalletMovement::class, 'wallet_movement_id');
    }

    public function isCredited(): bool
    {
        return $this->wallet_movement_id !== null;
    }
}

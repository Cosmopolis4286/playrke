<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Models;

use App\Models\Currency;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dirección de depósito derivada asignada a un usuario. Ver docblock de la
 * migración create_crypto_deposit_addresses_table — en esta fase (5,
 * esqueleto) `address` proviene de un provider STUB, nunca de una master
 * key real.
 */
class DepositAddress extends Model
{
    use SnowflakeId;

    public const NETWORK_TRON = 'TRON';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'currency_id' => 'string',
        'derivation_index' => 'integer',
    ];

    protected $fillable = [
        'user_id',
        'currency_id',
        'network',
        'derivation_index',
        'address',
        'status',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'crypto_deposit_addresses';

    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

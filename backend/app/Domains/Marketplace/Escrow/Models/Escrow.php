<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Escrow\Models;

use App\Domains\Marketplace\Inventory\Models\PlayerAsset;
use App\Domains\Marketplace\Models\Order;
use App\Models\Currency;
use App\Models\User;
use App\Domains\Wallet\Models\WalletHold;
use App\Traits\Auditable;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contenedor único de una transacción P2P (dinero + activo). Ver
 * MARKETPLACE_ARCHITECTURE_AUDIT.md sección 17. Todas las transiciones
 * pasan por EscrowService — este modelo no expone métodos que muten status.
 */
class Escrow extends Model
{
    use SnowflakeId;
    use Auditable;

    protected $table = 'escrows';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_CREATED         = 'CREATED';
    public const STATUS_FUNDED          = 'FUNDED';
    public const STATUS_ASSET_LOCKED    = 'ASSET_LOCKED';
    public const STATUS_READY_TO_SETTLE = 'READY_TO_SETTLE';
    public const STATUS_RELEASED        = 'RELEASED';
    public const STATUS_CANCELLED       = 'CANCELLED';
    public const STATUS_REFUNDED        = 'REFUNDED';
    public const STATUS_DISPUTED        = 'DISPUTED';
    public const STATUS_EXPIRED         = 'EXPIRED';

    public const RESOLUTION_BUYER_WINS  = 'BUYER_WINS';
    public const RESOLUTION_SELLER_WINS = 'SELLER_WINS';
    public const RESOLUTION_PARTIAL     = 'PARTIAL';
    public const RESOLUTION_CANCELLED   = 'CANCELLED';

    protected $fillable = [
        'order_id',
        'buyer_id',
        'seller_id',
        'player_asset_id',
        'quantity',
        'amount',
        'fee_amount',
        'seller_amount',
        'currency_id',
        'wallet_hold_id',
        'status',
        'expires_at',
        'dispute_reason',
        'resolution',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'id'               => 'string',
        'order_id'         => 'string',
        'buyer_id'         => 'string',
        'seller_id'        => 'string',
        'player_asset_id'  => 'string',
        'currency_id'      => 'string',
        'wallet_hold_id'   => 'string',
        'resolved_by'      => 'string',
        'quantity'         => 'integer',
        'amount'           => 'float',
        'fee_amount'       => 'float',
        'seller_amount'    => 'float',
        'expires_at'       => 'datetime',
        'resolved_at'      => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function playerAsset(): BelongsTo
    {
        return $this->belongsTo(PlayerAsset::class, 'player_asset_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function walletHold(): BelongsTo
    {
        return $this->belongsTo(WalletHold::class, 'wallet_hold_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isActive(): bool
    {
        return !in_array($this->status, [
            self::STATUS_RELEASED,
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED,
            self::STATUS_EXPIRED,
        ], true);
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Models;

use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Orden de compra sobre un Listing. Ver docblock de la migración
 * create_marketplace_orders_table: en Fase 2 solo reserva el listing; el
 * escrow (Fase 3) es quien mueve dinero/activo y marca COMPLETED.
 */
class Order extends Model
{
    use SnowflakeId;
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_ESCROW = 'ESCROW';
    public const STATUS_FAILED = 'FAILED';

    public const STATUS_PENDING = 'PENDING';
    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
        'listing_id' => 'string',
        'buyer_id' => 'string',
        'escrow_id' => 'string',
        'quantity' => 'integer',
        'price' => 'float',
        'fee_amount' => 'float',
    ];

    protected $fillable = [
        'listing_id',
        'buyer_id',
        'quantity',
        'price',
        'fee_amount',
        'escrow_id',
        'status',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'marketplace_orders';

    public function buyer() : BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function listing() : BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function totalAmount() : float
    {
        return round($this->price * $this->quantity, 2);
    }
}

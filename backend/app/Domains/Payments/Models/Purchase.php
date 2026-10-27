<?php

declare(strict_types=1);

namespace App\Domains\Payments\Models;

use App\Domains\Marketplace\Inventory\Models\PlayerAsset;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ledger de "qué se entregó a quién y por qué pago" — separado de
 * wallet_movements y crypto_transactions. payment_order_id es UNIQUE a nivel
 * de esquema: es la defensa estructural contra doble-entrega, no una
 * convención de aplicación. Ver PAYMENTS_USDT_TRC20_DESIGN.md sección 4.
 */
class Purchase extends Model
{
    use SnowflakeId;
    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'payment_order_id' => 'string',
        'payment_product_id' => 'string',
        'player_asset_id' => 'string',
        'delivered_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'payment_order_id',
        'payment_product_id',
        'player_asset_id',
        'delivered_at',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'purchases';

    public function paymentOrder() : BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class, 'payment_order_id');
    }

    public function paymentProduct() : BelongsTo
    {
        return $this->belongsTo(PaymentProduct::class, 'payment_product_id');
    }

    public function playerAsset() : BelongsTo
    {
        return $this->belongsTo(PlayerAsset::class, 'player_asset_id');
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

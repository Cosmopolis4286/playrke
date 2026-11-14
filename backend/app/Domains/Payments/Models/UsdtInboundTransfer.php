<?php

declare(strict_types=1);

namespace App\Domains\Payments\Models;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transferencia USDT-TRC20 observada en la dirección receptora. Se registra
 * una sola vez (tx_hash único): es la defensa contra procesar dos veces el
 * mismo pago y el listado de pagos sin dueño para tesorería.
 */
class UsdtInboundTransfer extends Model
{
    use SnowflakeId;

    public const STATUS_IGNORED = 'IGNORED';
    public const STATUS_LATE = 'LATE';
    public const STATUS_MATCHED = 'MATCHED';
    public const STATUS_UNMATCHED = 'UNMATCHED';

    protected $casts = [
        'amount' => 'float',
        'amount_units' => 'integer',
        'block_time' => 'datetime',
    ];

    protected $fillable = [
        'tx_hash',
        'from_address',
        'to_address',
        'amount',
        'amount_units',
        'block_time',
        'status',
        'usdt_deposit_order_id',
        'note',
    ];

    protected $table = 'usdt_inbound_transfers';

    public function order() : BelongsTo
    {
        return $this->belongsTo(UsdtDepositOrder::class, 'usdt_deposit_order_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Payments\Models;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log append-only de cada evaluación del watcher sobre una PaymentOrder.
 * Nunca se lee para tomar decisiones de negocio — solo trazabilidad para
 * soporte/auditoría. Ver PAYMENTS_USDT_TRC20_DESIGN.md sección 4.
 */
class PaymentAttempt extends Model
{
    use SnowflakeId;
    public const ACTION_CONFIRM = 'confirm';
    public const ACTION_CREDIT = 'credit';

    public const ACTION_DETECT = 'detect';
    public const RESULT_ERROR = 'ERROR';
    public const RESULT_REJECTED = 'REJECTED';

    public const RESULT_SUCCESS = 'SUCCESS';

    public const UPDATED_AT = null;
    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
        'payment_order_id' => 'string',
    ];

    protected $fillable = [
        'payment_order_id',
        'tx_hash',
        'attempted_action',
        'result',
        'reason',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'payment_attempts';

    public function paymentOrder() : BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class, 'payment_order_id');
    }
}

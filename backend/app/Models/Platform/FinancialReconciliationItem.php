<?php

namespace App\Models\Platform;

use App\Models\Currency;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alerta de conciliación financiera: un movimiento de dinero que el sistema
 * no pudo completar solo y requiere acción manual. Ver FinancialReconciliationService.
 */
class FinancialReconciliationItem extends Model
{
    use SnowflakeId;

    public const STATUS_OPEN     = 'OPEN';
    public const STATUS_RESOLVED = 'RESOLVED';

    /** La wallet de plataforma no tenía saldo para devolver el RKE de una venta reembolsada. */
    public const TYPE_RKE_REVERSAL_FAILED = 'RKE_REVERSAL_FAILED';
    /** El pozo del jackpot no alcanzaba para devolver el aporte de una venta reembolsada. */
    public const TYPE_JACKPOT_REVERSAL_SHORTFALL = 'JACKPOT_REVERSAL_SHORTFALL';
    /** No se pudo acreditar al creador su comisión al cerrar la sala. */
    public const TYPE_CREATOR_PAYOUT_FAILED = 'CREATOR_PAYOUT_FAILED';
    /** Lote reembolsado vendido antes del desglose financiero: no se pudo revertir su venta. */
    public const TYPE_LEGACY_LOT_NOT_REVERSED = 'LEGACY_LOT_NOT_REVERSED';

    protected $fillable = [
        'type', 'status', 'currency_id', 'amount', 'room_id', 'lot_id', 'idempotency_key',
        'message', 'details', 'resolved_at', 'resolved_by', 'resolution_notes',
    ];

    protected $casts = [
        'id'          => 'string',
        'currency_id' => 'string',
        'room_id'     => 'string',
        'lot_id'      => 'string',
        'resolved_by' => 'string',
        'amount'      => 'decimal:4',
        'details'     => 'array',
        'resolved_at' => 'datetime',
    ];

    public function currency(): BelongsTo { return $this->belongsTo(Currency::class, 'currency_id'); }
}

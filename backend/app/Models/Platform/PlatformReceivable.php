<?php

namespace App\Models\Platform;

use App\Models\Currency;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuenta por cobrar de la plataforma: comisión RKE de una venta cuyo dinero
 * quedó en manos del comercio (venta fuera del sistema). Se acredita a la
 * wallet de plataforma recién al marcarse SETTLED. Ver PlatformReceivableService.
 */
class PlatformReceivable extends Model
{
    use SnowflakeId;

    public const STATUS_PENDING   = 'PENDING';
    public const STATUS_SETTLED   = 'SETTLED';
    public const STATUS_CANCELLED = 'CANCELLED';

    /** Deudas previas: solo RKE. */
    public const SOURCE_BINGO_RKE = 'BINGO_RKE';
    /** Venta fuera del sistema: RKE + jackpot + bote. */
    public const SOURCE_BINGO_SALE = 'BINGO_SALE';

    protected $fillable = [
        'source', 'debtor_user_id', 'currency_id', 'room_id', 'lot_id', 'amount',
        'rke_amount', 'jackpot_amount', 'prize_pool_amount',
        'status', 'settled_at', 'settled_by', 'cancelled_at', 'notes',
    ];

    protected $casts = [
        'id'             => 'string',
        'debtor_user_id' => 'string',
        'currency_id'    => 'string',
        'room_id'        => 'string',
        'lot_id'         => 'string',
        'settled_by'     => 'string',
        'amount'         => 'decimal:2',
        'rke_amount'        => 'decimal:2',
        'jackpot_amount'    => 'decimal:2',
        'prize_pool_amount' => 'decimal:2',
        'settled_at'     => 'datetime',
        'cancelled_at'   => 'datetime',
    ];

    public function debtor(): BelongsTo { return $this->belongsTo(User::class, 'debtor_user_id'); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class, 'currency_id'); }
}

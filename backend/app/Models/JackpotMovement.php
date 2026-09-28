<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento del ledger de un jackpot: registra cada aporte con
 * balance_before/balance_after, análogo a WalletMovement.
 *
 * @property string $id
 * @property string $jackpot_id
 * @property string|null $room_id  Sala de Bingo que originó el aporte
 * @property string $type         CONTRIBUTION (único valor por ahora)
 * @property float  $amount
 * @property float  $balance_before
 * @property float  $balance_after
 * @property string|null $description
 * @property string|null $idempotency_key
 *
 * @property-read Jackpot $jackpot
 */
class JackpotMovement extends Model
{
    use \App\Traits\SnowflakeId;

    protected $table = 'jackpot_movements';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public const TYPE_CONTRIBUTION = 'CONTRIBUTION';
    /** Reversa de un aporte cuya venta se reembolsó (JackpotLedgerService::reverseContribution). */
    public const TYPE_REVERSAL = 'REVERSAL';

    protected $fillable = [
        'jackpot_id',
        'room_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'idempotency_key',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'float',
        'balance_before' => 'float',
        'balance_after' => 'float',
        'metadata' => 'array',
    ];

    public function jackpot(): BelongsTo
    {
        return $this->belongsTo(Jackpot::class, 'jackpot_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Games\Bingo\Models\Room\BingoRoom::class, 'room_id');
    }
}

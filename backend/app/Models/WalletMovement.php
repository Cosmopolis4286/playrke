<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para los movimientos de billeteras.
 *
 * Registra cada entrada o salida de fondos en una billetera:
 * depósitos, retiros, compras de cartones y premios ganados.
 *
 * @property string $id
 * @property string $wallet_id
 * @property string $user_id
 * @property string $type           DEPOSIT | WITHDRAWAL | PURCHASE | PRIZE
 * @property string $status         PENDING | COMPLETED | CANCELLED | REJECTED
 * @property float  $amount
 * @property float  $balance_before
 * @property float  $balance_after
 * @property string|null $description
 * @property string|null $reference_id  ID externo (cartón, premio, etc.)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Wallet   $wallet
 * @property-read User     $user
 */
class WalletMovement extends Model
{
    use \App\Traits\SnowflakeId;

    protected $table      = 'wallet_movements';
    protected $primaryKey = 'id';
    public    $incrementing = false;
    protected $keyType    = 'string';

    public const TYPE_DEPOSIT        = 'DEPOSIT';
    public const TYPE_WITHDRAWAL     = 'WITHDRAWAL';
    public const TYPE_PURCHASE       = 'PURCHASE';
    public const TYPE_PRIZE          = 'PRIZE';
    public const TYPE_TRIVIA_ENTRY   = 'TRIVIA_ENTRY';
    public const TYPE_RKE_COMMISSION = 'RKE_COMMISSION';

    public const STATUS_PENDING    = 'PENDING';
    public const STATUS_COMPLETED  = 'COMPLETED';
    public const STATUS_CANCELLED  = 'CANCELLED';
    public const STATUS_REJECTED   = 'REJECTED';

    /** Naturaleza del premio, guardada en metadata.prize_type para movimientos TYPE_PRIZE. */
    public const PRIZE_TYPE_DIGITAL  = 'DIGITAL';
    public const PRIZE_TYPE_PHYSICAL = 'PHYSICAL';

    public const PRIZE_TYPES = [
        self::PRIZE_TYPE_DIGITAL  => 'Premio digital',
        self::PRIZE_TYPE_PHYSICAL => 'Premio físico',
    ];

    public const TYPES = [
        self::TYPE_DEPOSIT        => 'Depósito',
        self::TYPE_WITHDRAWAL     => 'Retiro',
        self::TYPE_PURCHASE       => 'Compra de cartón',
        self::TYPE_PRIZE          => 'Premio ganado',
        self::TYPE_TRIVIA_ENTRY   => 'Entrada Trivia',
        self::TYPE_RKE_COMMISSION => 'Comisión RKE',
    ];

    protected $fillable = [
        'wallet_id',
        'user_id',
        'type',
        'status',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference_id',
        'receipt_path',
        'metadata',
        'admin_notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'amount'         => 'float',
        'balance_before' => 'float',
        'balance_after'  => 'float',
        'metadata'       => 'array',
        'processed_at'   => 'datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** El movimiento suma fondos (depósito, premio o comisión de plataforma). */
    public function isCredit(): bool
    {
        return in_array($this->type, [
            self::TYPE_DEPOSIT,
            self::TYPE_PRIZE,
            self::TYPE_RKE_COMMISSION,
        ]);
    }

    /** Naturaleza del premio (DIGITAL|PHYSICAL) guardada en metadata, solo aplica a TYPE_PRIZE. */
    public function getPrizeTypeAttribute(): ?string
    {
        return $this->metadata['prize_type'] ?? null;
    }

    /** ID del juego/sala que originó el premio, guardado en metadata, solo aplica a TYPE_PRIZE. */
    public function getGameIdAttribute(): ?string
    {
        return $this->metadata['game_id'] ?? null;
    }

    /** El movimiento resta fondos (retiro o compra). */
    public function isDebit(): bool
    {
        return in_array($this->type, [self::TYPE_WITHDRAWAL, self::TYPE_PURCHASE]);
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForWallet($query, string $walletId)
    {
        return $query->where('wallet_id', $walletId);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }
}

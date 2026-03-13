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

    public const TYPE_DEPOSIT    = 'DEPOSIT';
    public const TYPE_WITHDRAWAL = 'WITHDRAWAL';
    public const TYPE_PURCHASE   = 'PURCHASE';
    public const TYPE_PRIZE      = 'PRIZE';

    public const TYPES = [
        self::TYPE_DEPOSIT    => 'Depósito',
        self::TYPE_WITHDRAWAL => 'Retiro',
        self::TYPE_PURCHASE   => 'Compra de cartón',
        self::TYPE_PRIZE      => 'Premio ganado',
    ];

    protected $fillable = [
        'wallet_id',
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference_id',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
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

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** El movimiento suma fondos (depósito o premio). */
    public function isCredit(): bool
    {
        return in_array($this->type, [self::TYPE_DEPOSIT, self::TYPE_PRIZE]);
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

<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Models;

use App\Models\MovementType;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para los movimientos de billeteras.
 *
 * Registra cada entrada o salida de fondos en una billetera:
 * depósitos, retiros, compras de cartones y premios ganados.
 *
 * @property string         $id
 * @property string         $wallet_id
 * @property string         $user_id
 * @property string         $type            DEPOSIT | WITHDRAWAL | PURCHASE | PRIZE
 * @property string         $status          PENDING | COMPLETED | CANCELLED | REJECTED
 * @property float          $amount
 * @property float          $balance_before
 * @property float          $balance_after
 * @property string|null    $description
 * @property string|null    $reference_id    ID externo (cartón, premio, etc.)
 * @property string|null    $idempotency_key Clave única para prevenir doble acreditación en pagos reintentables
 * @property string|null    $receipt_hash    sha256 del comprobante subido, para detectar reuso
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property Wallet         $wallet
 * @property User           $user
 */
class WalletMovement extends Model
{
    use \App\Traits\SnowflakeId;
    use Auditable;

    /** Naturaleza del premio, guardada en metadata.prize_type para movimientos TYPE_PRIZE. */
    public const PRIZE_TYPE_DIGITAL = 'DIGITAL';
    public const PRIZE_TYPE_PHYSICAL = 'PHYSICAL';

    public const PRIZE_TYPES = [
        self::PRIZE_TYPE_DIGITAL => 'Premio digital',
        self::PRIZE_TYPE_PHYSICAL => 'Premio físico',
    ];
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_REJECTED = 'REJECTED';
    public const TYPE_BONUS_PRIZE = 'BONUS_PRIZE';
    public const TYPE_CRYPTO_DEPOSIT = 'CRYPTO_DEPOSIT';
    public const TYPE_CRYPTO_WITHDRAWAL = 'CRYPTO_WITHDRAWAL';

    public const TYPE_DEPOSIT = 'DEPOSIT';
    public const TYPE_MARKETPLACE_FEE = 'MARKETPLACE_FEE';
    public const TYPE_MARKETPLACE_PURCHASE = 'MARKETPLACE_PURCHASE';
    public const TYPE_MARKETPLACE_SALE = 'MARKETPLACE_SALE';
    public const TYPE_PRIZE = 'PRIZE';
    public const TYPE_PRIZE_REVERSAL = 'PRIZE_REVERSAL';
    public const TYPE_PURCHASE = 'PURCHASE';
    public const TYPE_REFUND = 'REFUND';
    public const TYPE_RKE_COMMISSION = 'RKE_COMMISSION';
    public const TYPE_TRIVIA_ENTRY = 'TRIVIA_ENTRY';
    public const TYPE_WITHDRAWAL = 'WITHDRAWAL';

    /** @deprecated Usar MovementType::catalog() — se mantiene mientras el frontend termina de migrar. */
    public const TYPES = [
        self::TYPE_DEPOSIT => 'Depósito',
        self::TYPE_WITHDRAWAL => 'Retiro',
        self::TYPE_PURCHASE => 'Compra de cartón',
        self::TYPE_PRIZE => 'Premio ganado',
        self::TYPE_BONUS_PRIZE => 'Premio bono (no retirable)',
        self::TYPE_REFUND => 'Reembolso',
        self::TYPE_TRIVIA_ENTRY => 'Entrada Trivia',
        self::TYPE_RKE_COMMISSION => 'Comisión RKE',
    ];
    public $incrementing = false;

    protected $casts = [
        'amount' => 'float',
        'balance_before' => 'float',
        'balance_after' => 'float',
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
        'idempotency_key',
        'receipt_path',
        'receipt_hash',
        'metadata',
        'admin_notes',
        'processed_by',
        'processed_at',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'wallet_movements';

    /** ID del juego/sala que originó el premio, guardado en metadata, solo aplica a TYPE_PRIZE. */
    public function getGameIdAttribute() : ?string
    {
        return $this->metadata['game_id'] ?? null;
    }

    /** Naturaleza del premio (DIGITAL|PHYSICAL) guardada en metadata, solo aplica a TYPE_PRIZE. */
    public function getPrizeTypeAttribute() : ?string
    {
        return $this->metadata['prize_type'] ?? null;
    }

    public function getTypeLabel() : string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** El movimiento suma fondos a la wallet, según el catálogo MovementType. */
    public function isCredit() : bool
    {
        return (bool) (MovementType::catalog()->get($this->type)?->is_credit ?? false);
    }

    /** El movimiento resta fondos de la wallet, según el catálogo MovementType. */
    public function isDebit() : bool
    {
        return MovementType::catalog()->get($this->type)?->is_credit === false;
    }

    public function processedBy() : BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForWallet($query, string $walletId)
    {
        return $query->where('wallet_id', $walletId);
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function wallet() : BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }
}

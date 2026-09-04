<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Models;

use App\Models\GameSession;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reserva de saldo comprometido en una partida en curso.
 *
 * A diferencia de WalletMovement (dinero ya movido), un hold representa
 * dinero apartado que todavía puede liberarse sin gasto (ej. un
 * transportista se retira antes de destino) o capturarse como gasto real.
 * No modifica wallets.balance/bonus_balance mientras está HELD — solo se
 * descuenta del saldo disponible calculado en Wallet::getAvailableBalance().
 *
 * @property string              $id
 * @property string              $wallet_id
 * @property string|null         $game_session_id
 * @property float               $amount
 * @property string              $status               HELD | CAPTURED | RELEASED
 * @property string|null         $captured_movement_id
 * @property string|null         $description
 * @property \Carbon\Carbon|null $resolved_at
 * @property Wallet              $wallet
 */
class WalletHold extends Model
{
    use \App\Traits\SnowflakeId;
    use Auditable;
    public const STATUS_CAPTURED = 'CAPTURED';

    public const STATUS_HELD = 'HELD';
    public const STATUS_RELEASED = 'RELEASED';
    public $incrementing = false;

    protected $casts = [
        'amount' => 'float',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'wallet_id',
        'game_session_id',
        'amount',
        'status',
        'captured_movement_id',
        'description',
        'resolved_at',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'wallet_holds';

    public function gameSession() : BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function isActive() : bool
    {
        return $this->status === self::STATUS_HELD;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_HELD);
    }

    public function scopeForWallet($query, string $walletId)
    {
        return $query->where('wallet_id', $walletId);
    }

    public function wallet() : BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }
}

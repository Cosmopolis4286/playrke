<?php

namespace App\Models;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para las wallets de usuarios
 *
 * Representa una wallet que pertenece a un usuario y contiene un balance
 * en una moneda específica. Cada wallet tiene un estado que determina
 * si está activa, inactiva o bloqueada.
 *
 * @package App\Models
 * @author Sistema de Gestión de Wallets
 * @version 1.0
 *
 * @property string $id ID único de la wallet
 * @property string $user_id ID del usuario propietario
 * @property string $currency_id ID de la moneda
 * @property float $balance Balance retirable de la wallet
 * @property float $bonus_balance Saldo no retirable (bono), solo usable dentro de la app
 * @property string $status Estado de la wallet
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 *
 * @property-read User $user Usuario propietario
 * @property-read Currency $currency Moneda de la wallet
 */
class Wallet extends Model
{
    use \App\Traits\SnowflakeId, HasFactory;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'wallets';

    /**
     * Clave primaria del modelo
     */
    protected $primaryKey = 'id';

    /**
     * Indica si el ID es auto-incremental
     */
    public $incrementing = false;

    /**
     * Tipo de la clave primaria
     */
    protected $keyType = 'string';

    /**
     * Estados de wallet disponibles
     */
    public const STATUSES = [
        'ACTIVE' => 'Activa',
        'INACTIVE' => 'Inactiva',
        'BLOCKED' => 'Bloqueada',
    ];

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'user_id',
        'currency_id',
        'balance',
        'bonus_balance',
        'status',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'balance' => 'float',
        'bonus_balance' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el usuario propietario
     *
     * @return BelongsTo<User, Wallet>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con la moneda
     *
     * @return BelongsTo<Currency, Wallet>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relación con las reservas de saldo (holds) de esta wallet.
     *
     * @return HasMany<WalletHold, Wallet>
     */
    public function holds(): HasMany
    {
        return $this->hasMany(WalletHold::class, 'wallet_id');
    }

    /**
     * Obtener la descripción del estado de la wallet
     *
     * @return string
     */
    public function getStatusDescription(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Verificar si la wallet está activa
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Verificar si la wallet está bloqueada
     *
     * @return bool
     */
    public function isBlocked(): bool
    {
        return $this->status === 'BLOCKED';
    }

    /**
     * Verificar si la wallet está inactiva
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status === 'INACTIVE';
    }

    /**
     * Verificar si la wallet tiene saldo RETIRABLE suficiente (columna balance
     * únicamente). Usado solo para validar retiros — nunca usar para validar
     * gasto dentro de la app (usar hasSufficientPlayableBalance()).
     *
     * @param float $amount Cantidad a verificar
     * @return bool
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Saldo total disponible para gastar dentro de la app (bono + retirable).
     * Nunca usar este valor para validar retiros.
     */
    public function getPlayableBalance(): float
    {
        return (float) $this->balance + (float) $this->bonus_balance;
    }

    /**
     * Verifica si la wallet tiene saldo suficiente para gastar dentro de la
     * app (entradas de juego, compra de cartones), combinando bono + retirable.
     */
    public function hasSufficientPlayableBalance(float $amount): bool
    {
        return $this->getPlayableBalance() >= $amount;
    }

    /**
     * Saldo jugable (bono + retirable) menos las reservas (WalletHold) activas.
     * Usar esta cifra para validar si el usuario puede entrar a una NUEVA
     * partida/reserva — evita que un saldo ya comprometido en otra partida en
     * curso se cuente dos veces. No confundir con getPlayableBalance(), que
     * ignora holds y sirve para operaciones que capturan/liberan un hold ya
     * existente sobre esta misma wallet.
     */
    public function getAvailableBalance(): float
    {
        $activeHolds = (float) $this->holds()->active()->sum('amount');

        return $this->getPlayableBalance() - $activeHolds;
    }

    /**
     * Verifica si la wallet tiene saldo disponible suficiente (jugable menos
     * holds activos) para una nueva reserva de $amount.
     */
    public function hasSufficientAvailableBalance(float $amount): bool
    {
        return $this->getAvailableBalance() >= $amount;
    }

    /**
     * Scope para obtener solo wallets activas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope para obtener solo wallets bloqueadas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', 'BLOCKED');
    }

    /**
     * Scope para obtener wallets por estado
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}

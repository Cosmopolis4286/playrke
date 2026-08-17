<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Otorgamiento de un permiso específico a un usuario, con soporte de
 * expiración — reemplaza al antiguo users.specific_permissions (JSON plano
 * de strings sin metadata). Un usuario puede tener a lo sumo un grant ACTIVO
 * (revoked_at null) por permission_name (ver índice único de la migración).
 *
 * @property string $id
 * @property string $user_id
 * @property string $permission_name
 * @property string|null $granted_by
 * @property string|null $reason
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $revoked_at
 * @property string|null $revoked_reason
 * @property \Carbon\Carbon|null $expiring_notified_at
 *
 * @property-read User $user
 * @property-read User|null $grantor
 */
class UserSpecificPermissionGrant extends Model
{
    use \App\Traits\SnowflakeId;
    use Auditable;

    protected $table = 'user_specific_permission_grants';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public const REVOKED_REASON_EXPIRED = 'EXPIRED';
    public const REVOKED_REASON_MANUAL  = 'MANUAL';

    protected $fillable = [
        'user_id',
        'permission_name',
        'granted_by',
        'reason',
        'expires_at',
        'revoked_at',
        'revoked_reason',
        'expiring_notified_at',
    ];

    protected $casts = [
        'expires_at'            => 'datetime',
        'revoked_at'            => 'datetime',
        'expiring_notified_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
    }

    /**
     * Grants activos cuyo expires_at cae dentro de los próximos $days días,
     * y que todavía no recibieron el aviso previo — usado por el job de
     * aviso de expiración próxima.
     */
    public function scopeExpiringSoon(Builder $query, int $days): Builder
    {
        return $query
            ->whereNotNull('expires_at')
            ->whereNull('expiring_notified_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && !$this->isExpired();
    }
}

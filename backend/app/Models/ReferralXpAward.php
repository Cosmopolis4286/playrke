<?php

namespace App\Models;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro estructurado de cada XP otorgado por referido verificado — ver
 * AwardReferralXpOnVerifiedListener. Pensado para que un admin detecte
 * patrones de abuso (varios referidos desde la misma IP) y revierta un
 * award puntual manualmente; no hay reversión automática.
 */
class ReferralXpAward extends Model
{
    use SnowflakeId;

    protected $table = 'referral_xp_awards';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'xp_amount',
        'referred_registration_ip',
        'revoked_at',
        'revoked_by',
        'revoked_reason',
    ];

    protected $casts = [
        'id' => 'string',
        'referrer_id' => 'string',
        'referred_user_id' => 'string',
        'revoked_by' => 'string',
        'xp_amount' => 'integer',
        'revoked_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function revokedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    public function scopeFromIp($query, string $ip)
    {
        return $query->where('referred_registration_ip', $ip);
    }
}

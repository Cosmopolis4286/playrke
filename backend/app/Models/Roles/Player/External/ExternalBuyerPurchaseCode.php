<?php

namespace App\Models\Roles\Player\External;

use App\Models\Country;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Código de compra que un vendedor otorga a un comprador RECURRENTE para que
 * compre por su cuenta desde el enlace público del vendedor.
 *
 * Vigente mientras: no esté revocado, no haya vencido (expires_at) y le
 * queden usos (uses_count < max_uses). Ver ExternalBuyerPurchaseCodeService.
 */
class ExternalBuyerPurchaseCode extends Model
{
    use SnowflakeId;

    protected $table = 'external_buyer_purchase_codes';

    protected $fillable = [
        'seller_user_id',
        'country_id',
        'phone',
        'code',
        'max_uses',
        'uses_count',
        'expires_at',
        'revoked_at',
        'last_used_at',
    ];

    protected $casts = [
        'id'             => 'string',
        'seller_user_id' => 'string',
        'country_id'     => 'string',
        'max_uses'       => 'integer',
        'uses_count'     => 'integer',
        'expires_at'     => 'datetime',
        'revoked_at'     => 'datetime',
        'last_used_at'   => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /** Códigos utilizables ahora: no revocados, no vencidos y con usos disponibles. */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->whereColumn('uses_count', '<', 'max_uses');
    }

    public function remainingUses(): int
    {
        return max(0, $this->max_uses - $this->uses_count);
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null
            && $this->expires_at?->isFuture()
            && $this->uses_count < $this->max_uses;
    }

    /** Estado legible para el vendedor. */
    public function statusLabel(): string
    {
        return match (true) {
            $this->revoked_at !== null              => 'Revocado',
            $this->uses_count >= $this->max_uses    => 'Agotado',
            !$this->expires_at?->isFuture()         => 'Vencido',
            default                                 => 'Vigente',
        };
    }
}

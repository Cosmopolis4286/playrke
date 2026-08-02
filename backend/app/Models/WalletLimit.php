<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WalletLimit extends Model
{
    protected $table = 'wallet_limits';

    private const CACHE_KEY = 'wallet_limits:catalog';

    protected $fillable = [
        'currency_id',
        'kyc_level',
        'min_deposit',
        'max_deposit',
        'min_per_transaction',
        'max_per_transaction',
        'max_daily',
        'max_weekly',
        'max_monthly',
        'is_active',
    ];

    protected $casts = [
        'min_deposit'         => 'float',
        'max_deposit'         => 'float',
        'min_per_transaction' => 'float',
        'max_per_transaction' => 'float',
        'max_daily'           => 'float',
        'max_weekly'          => 'float',
        'max_monthly'         => 'float',
        'is_active'           => 'boolean',
    ];

    public function currency(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Resuelve el límite aplicable: primero busca por currency_id + kyc_level,
     * luego el fallback global (currency_id IS NULL). Cacheado 1h por ser
     * consultado en cada solicitud de depósito/retiro.
     */
    public static function resolve(string $currencyId, int $kycLevel): ?self
    {
        $catalog = Cache::remember(self::CACHE_KEY, 3600, fn () => static::query()->where('is_active', true)->get());

        return $catalog
            ->where('kyc_level', $kycLevel)
            ->filter(fn (self $limit) => $limit->currency_id === $currencyId || $limit->currency_id === null)
            ->sortBy(fn (self $limit) => $limit->currency_id === null ? 1 : 0) // específico primero
            ->first();
    }

    public static function forgetCatalogCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

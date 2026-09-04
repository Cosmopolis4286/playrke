<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Models;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WalletLimit extends Model
{
    private const CACHE_KEY = 'wallet_limits:catalog';

    protected $casts = [
        'min_deposit' => 'float',
        'max_deposit' => 'float',
        'min_per_transaction' => 'float',
        'max_per_transaction' => 'float',
        'max_daily' => 'float',
        'max_weekly' => 'float',
        'max_monthly' => 'float',
        'is_active' => 'boolean',
    ];

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
    protected $table = 'wallet_limits';

    public static function forgetCatalogCache() : void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Resuelve el límite aplicable: primero busca por currency_id + kyc_level,
     * luego el fallback global (currency_id IS NULL). Cacheado 1h por ser
     * consultado en cada solicitud de depósito/retiro.
     */
    public static function resolve(string $currencyId, int $kycLevel) : ?self
    {
        $catalog = Cache::remember(self::CACHE_KEY, 3600, fn () => static::query()->where('is_active', true)->get());

        // currency_id es un foreignId (unsignedBigInteger) sin cast declarado
        // en $casts — PDO puede devolverlo como int o string según el driver/
        // entorno (confirmado: difiere entre CLI local y el contenedor Docker
        // de test). $currencyId (snowflake) llega siempre como string —
        // comparar con === sin normalizar ambos lados a string hace que el
        // match falle en silencio cuando el driver castea a int, y resolve()
        // devuelve null pese a existir la fila.
        return $catalog
            ->where('kyc_level', $kycLevel)
            ->filter(fn (self $limit) => $limit->currency_id === null || (string) $limit->currency_id === $currencyId)
            ->sortBy(fn (self $limit) => $limit->currency_id === null ? 1 : 0) // específico primero
            ->first();
    }

    public function currency() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}

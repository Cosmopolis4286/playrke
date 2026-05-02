<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalLimit extends Model
{
    protected $table = 'withdrawal_limits';

    protected $fillable = [
        'currency_id',
        'kyc_level',
        'min_per_transaction',
        'max_per_transaction',
        'max_daily',
        'max_weekly',
        'max_monthly',
        'is_active',
    ];

    protected $casts = [
        'min_per_transaction' => 'float',
        'max_per_transaction' => 'float',
        'max_daily'           => 'float',
        'max_weekly'          => 'float',
        'max_monthly'         => 'float',
        'is_active'           => 'boolean',
    ];

    /**
     * Resuelve el límite aplicable: primero busca por currency_id + kyc_level,
     * luego el fallback global (currency_id IS NULL).
     */
    public static function resolve(string $currencyId, int $kycLevel): ?self
    {
        return static::where('kyc_level', $kycLevel)
            ->where(fn ($q) => $q->where('currency_id', $currencyId)->orWhereNull('currency_id'))
            ->where('is_active', true)
            ->orderByRaw('currency_id IS NULL ASC') // específico primero
            ->first();
    }
}

<?php

namespace App\Services\Roles\SuperAdmin\Dashboard;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;

/**
 * Tasas de cambio activas por moneda, para SuperAdmin — sistema
 * multi-moneda sin ningún panel de tasas vigentes hasta ahora. Muestra la
 * tasa oficial más reciente por moneda.
 *
 * exchange_rates es EXCLUSIVAMENTE de plataforma: las tasas propias de
 * cada commerce viven en su propia tabla separada
 * (commerces_exchange_rates, con commerce_id obligatorio), no mezcladas
 * aquí — no hace falta ni es correcto filtrar por user_id para
 * distinguirlas. user_id en esta tabla es NOT NULL (auditoría de quién
 * registró/actualizó la tasa, ver migración create_exchange_rates_table),
 * nunca identifica "tasa de plataforma vs. de comercio"; un
 * ->whereNull('user_id') aquí siempre habría devuelto un array vacío.
 */
final class ExchangeRateSummaryService
{
    private const CACHE_KEY = 'superadmin:exchange-rate-summary';
    private const CACHE_TTL = 90;

    public function getSummary(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->buildSummary());
    }

    private function buildSummary(): array
    {
        $rates = ExchangeRate::active()
            ->with('currency:id,iso_code,symbol')
            ->latest('updated_at')
            ->get(['id', 'currency_id', 'amount', 'percentage', 'updated_at'])
            ->unique('currency_id')
            ->map(fn (ExchangeRate $rate) => [
                'currency_id'   => (string) $rate->currency_id,
                'currency_code' => $rate->currency?->iso_code ?? '—',
                'symbol'        => $rate->currency?->symbol ?? '',
                'amount'        => (float) $rate->amount,
                'percentage'    => (float) $rate->percentage,
                'updated_at'    => $rate->updated_at?->toIso8601String(),
            ])
            ->values();

        return [
            'rates' => $rates,
        ];
    }
}

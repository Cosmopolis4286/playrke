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

    /** Puntos de historial por moneda para el sparkline del widget — mismo
     *  criterio de "suficientes para una tendencia legible, sin sobrecargar
     *  el payload del dashboard" que buildSalesTrend() usa para otros
     *  widgets. */
    private const HISTORY_POINTS = 7;

    private function buildSummary(): array
    {
        $rates = ExchangeRate::active()
            ->with(['currency:id,iso_code,symbol', 'user:id,username,first_name,last_name'])
            ->latest('updated_at')
            ->get(['id', 'currency_id', 'user_id', 'amount', 'percentage', 'status', 'source', 'updated_at'])
            ->unique('currency_id')
            // unique() preserva el orden de la colección original
            // (->latest() ya la ordenaba desc), pero se reordena explícito
            // acá para no depender de ese detalle de implementación —
            // "más reciente primero" es un requisito del widget, no un
            // efecto colateral de otra query.
            ->sortByDesc('updated_at')
            ->map(fn (ExchangeRate $rate) => [
                'currency_id'   => (string) $rate->currency_id,
                'currency_code' => $rate->currency?->iso_code ?? '—',
                'symbol'        => $rate->currency?->symbol ?? '',
                'amount'        => (float) $rate->amount,
                'percentage'    => (float) $rate->percentage,
                'updated_at'    => $rate->updated_at?->toIso8601String(),
                'source'        => $rate->source ?? ExchangeRate::SOURCE_USER,
                'user_name'     => $this->displayName($rate),
                'history'       => $this->buildHistory((string) $rate->currency_id),
            ])
            ->values();

        return [
            'rates' => $rates,
        ];
    }

    /**
     * Nombre a mostrar de quién actualizó la tasa — username si existe,
     * si no el nombre completo, si no un placeholder. source=system no
     * cambia qué usuario se muestra (sigue siendo el dueño real de la
     * preferencia auto_update_enabled, ver HandleExchangeRatesCommand, no
     * un usuario "Sistema" genérico) — el frontend usa `source` para
     * decidir si antepone "Automático" o no, este método solo resuelve el
     * nombre humano.
     */
    private function displayName(ExchangeRate $rate): string
    {
        if (!$rate->user) {
            return '—';
        }

        if ($rate->user->username) {
            return $rate->user->username;
        }

        $fullName = trim("{$rate->user->first_name} {$rate->user->last_name}");

        return $fullName !== '' ? $fullName : '—';
    }

    /**
     * Últimos N puntos (ACTIVE + OLD, cualquier registro real de la
     * moneda — no solo el vigente) en orden cronológico ascendente, listos
     * para graficar directamente sin que el frontend tenga que invertir el
     * array (a diferencia de Historical.vue, que sí recibe orden
     * descendente desde getHistoryByCurrency() y usa reverse=true).
     *
     * 1 query por moneda dentro del map() de buildSummary() — N+1
     * deliberado, no un descuido: el catálogo de monedas soportadas es
     * pequeño (4 al momento de escribir esto, ver CurrencySeeder) y todo
     * getSummary() ya está cacheado 90s, así que el costo real es "hasta 4
     * queries extra cada 90s", no por request de usuario. Batchear esto
     * (una sola query con window function o agrupada en memoria) sería
     * prematuro mientras el catálogo se mantenga en ese orden de magnitud.
     */
    private function buildHistory(string $currencyId): array
    {
        return ExchangeRate::where('currency_id', $currencyId)
            ->whereIn('status', ['ACTIVE', 'OLD'])
            ->latest('updated_at')
            ->limit(self::HISTORY_POINTS)
            ->get(['amount', 'updated_at'])
            ->sortBy('updated_at')
            ->values()
            ->map(fn (ExchangeRate $rate) => [
                'date'   => $rate->updated_at?->toIso8601String(),
                'amount' => (float) $rate->amount,
            ])
            ->all();
    }
}

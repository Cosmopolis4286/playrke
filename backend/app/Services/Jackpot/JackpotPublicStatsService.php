<?php

namespace App\Services\Jackpot;

use App\Models\Games\Common\Jackpot\JackpotType;
use Illuminate\Support\Facades\Cache;

/**
 * Saldo público de los jackpots activos (Landing, GameRoomsShowcase) — mismo
 * patrón que PlatformStatsService. TTL más corto (15s, no 60s) porque el
 * jackpot se alimenta en cada venta de cartón (RoomMetricsService ->
 * JackpotLedgerService::contribute()) y necesita sentirse "vivo".
 */
final class JackpotPublicStatsService
{
    private const CACHE_TTL_SECONDS = 15;

    /**
     * Un JackpotType sin fila en `jackpots` aún (nunca recibió un aporte) se
     * expone con amount=0, no se omite — la vitrina debe listar siempre los
     * mismos jackpots aunque alguno todavía no tenga saldo.
     */
    public function list(): array
    {
        return Cache::remember('jackpot:public:list', self::CACHE_TTL_SECONDS, function () {
            return JackpotType::query()
                ->active()
                ->with(['jackpot:id,jackpot_type_id,amount', 'currency:id,iso_code,symbol'])
                ->get()
                ->map(fn (JackpotType $type) => [
                    'code' => $type->code,
                    'label' => $type->label,
                    'periodicity' => $type->periodicity,
                    'amount' => (float) ($type->jackpot?->amount ?? 0.0),
                    'currency' => [
                        'iso_code' => $type->currency?->iso_code,
                        'symbol' => $type->currency?->symbol,
                    ],
                ])
                ->values()
                ->all();
        });
    }
}

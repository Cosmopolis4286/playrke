<?php

namespace App\Http\Controllers\Platform;

use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Domains\Platform\WinEvent\Services\WinEventLeaderboardService;
use App\Domains\Platform\WinEvent\Services\WinEventService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\WinEvent\ReportExternalWinEventRequest;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints públicos del feed de ganancias en vivo de la plataforma.
 */
class WinEventController extends Controller
{
    private const LIMIT = 10;
    private const WINDOW_MINUTES = 5;

    public function __construct(
        private readonly WinEventService $winEventService,
        private readonly WinEventLeaderboardService $leaderboardService,
    ) {
    }

    /**
     * Ranking semanal de mayores ganadores en dinero, para la sección
     * "Ganadores" de la landing pública.
     */
    public function leaderboard() : JsonResponse
    {
        return $this->handleServiceJson(fn () => [
            'leaderboard' => $this->leaderboardService->weeklyTopWinners(),
        ]);
    }

    /**
     * Últimos eventos de ganancia, usados para sembrar el ticker antes de
     * que lleguen eventos en tiempo real por WebSocket.
     */
    public function recent() : JsonResponse
    {
        return $this->handleServiceJson(function() {
            $events = WinEvent::query()
                ->with(['user:id,first_name,last_name,username', 'currency:id,iso_code,symbol,decimal_places'])
                ->recent(self::WINDOW_MINUTES)
                ->latest('created_at')
                ->limit(self::LIMIT)
                ->get();

            return [
                'wins' => $events->map(fn (WinEvent $e) => $this->toPayload($e))->values(),
            ];
        });
    }

    /**
     * Reporta una ganancia desde una integración externa (POS físico,
     * proveedor de loterías, etc). Protegido por publicToken con scope
     * 'win-events.report' (ver routes/modules/public.php).
     */
    public function reportExternal(ReportExternalWinEventRequest $request) : JsonResponse
    {
        return $this->handleServiceJson(function() use ($request) {
            $data = $request->validated();

            $winEvent = $data['prize_kind'] === WinEvent::PRIZE_MONEY
                ? $this->winEventService->reportMoney(
                    userId: $data['user_id'] ?? null,
                    winnerNameOverride: $data['winner_name'] ?? null,
                    gameSlug: $data['game_slug'],
                    gameName: $data['game_name'],
                    amountUsd: (float) $data['amount_usd'],
                    amountLocal: (float) ($data['amount_local'] ?? $data['amount_usd']),
                    currencyId: $data['currency_id'] ?? null,
                    sourceType: WinEvent::SOURCE_EXTERNAL_API,
                    sourceReference: $data['source_reference'] ?? null,
                    metadata: $data['metadata'] ?? null,
                )
                : $this->winEventService->reportNonMoney(
                    userId: $data['user_id'] ?? null,
                    winnerNameOverride: $data['winner_name'] ?? null,
                    gameSlug: $data['game_slug'],
                    gameName: $data['game_name'],
                    prizeKind: $data['prize_kind'],
                    prizeLabel: $data['prize_label'],
                    sourceType: WinEvent::SOURCE_EXTERNAL_API,
                    sourceReference: $data['source_reference'] ?? null,
                    metadata: $data['metadata'] ?? null,
                );

            return ['win_event_id' => (string) $winEvent->id];
        });
    }

    private function toPayload(WinEvent $e) : array
    {
        $currency = $e->currency;

        return [
            'id' => (string) $e->id,
            'game' => $e->game_name,
            'game_slug' => $e->game_slug,
            'winner_name' => $e->maskedWinnerName(),
            'prize_kind' => $e->prize_kind,
            'prize_label' => $e->prize_label,
            'amount' => $e->amount_usd !== null ? (float) $e->amount_usd : null,
            'currency' => $currency?->iso_code ?? 'USD',
            'currency_symbol' => $currency?->symbol ?? '$',
            'is_usd' => ($currency?->iso_code ?? 'USD') === 'USD',
            'local_amount' => $e->amount_local !== null ? (float) $e->amount_local : null,
            'decimal_places' => $currency?->decimal_places ?? 2,
            'timestamp' => $e->created_at?->toISOString(),
        ];
    }
}

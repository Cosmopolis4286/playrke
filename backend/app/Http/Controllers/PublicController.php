<?php

namespace App\Http\Controllers;

use App\Contracts\AppParameterRepositoryInterface;
use App\Domains\Games\Bingo\Services\Room\BingoRoomContextService;
use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Domains\Platform\WinEvent\Services\WinEventLeaderboardService;
use App\Services\Jackpot\JackpotPublicStatsService;
use App\Services\Platform\PlatformStatsService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de páginas públicas
 *
 * Maneja la landing pública, login y páginas de información estática.
 */
class PublicController extends Controller
{
    private const LANDING_ROOMS_LIMIT = 3;
    private const LANDING_WINS_WINDOW_MINUTES = 5;
    private const LANDING_WINS_LIMIT = 6;

    /**
     * Inyecta el repositorio en el constructor
     */
    public function __construct(
        protected AppParameterRepositoryInterface $settings
    ) {}

    /**
     * Landing pública — punto de entrada del sitio para visitantes sin
     * sesión. No hay "producto" que vender fuera del juego mismo, así que
     * la landing muestra directamente salas destacadas (mismo DTO que
     * gameRoomsShowcase), el feed de ganancias recientes (mismo query que
     * WinEventController::recent()) y el acceso al verificador de cartón.
     * El login/registro tienen sus propias rutas (/login, /register).
     */
    public function root(
        BingoRoomContextService $contextService,
        PlatformStatsService $platformStats,
        WinEventLeaderboardService $leaderboardService,
        JackpotPublicStatsService $jackpotStats,
    ): Response {
        $register = $this->settings->getParameterStatus('REGISTER');

        $showcase = $contextService->publicShowcase()->toView();
        $showcase['rooms'] = collect($showcase['rooms'])->take(self::LANDING_ROOMS_LIMIT)->values()->all();

        $wins = WinEvent::query()
            ->with(['user:id,first_name,last_name,username', 'currency:id,iso_code,symbol,decimal_places'])
            ->recent(self::LANDING_WINS_WINDOW_MINUTES)
            ->latest('created_at')
            ->limit(self::LANDING_WINS_LIMIT)
            ->get()
            ->map(fn (WinEvent $e) => [
                'id'              => (string) $e->id,
                'game'            => $e->game_name,
                'game_slug'       => $e->game_slug,
                'winner_name'     => $e->maskedWinnerName(),
                'prize_kind'      => $e->prize_kind,
                'prize_label'     => $e->prize_label,
                'amount'          => $e->amount_usd !== null ? (float) $e->amount_usd : null,
                'currency_symbol' => $e->currency?->symbol ?? '$',
                'timestamp'       => $e->created_at?->toISOString(),
            ])
            ->values();

        return Inertia::render('Public/Landing', array_merge($showcase, [
            'inertiaOnly' => true,
            'register' => (bool) $register,
            'initialWins' => $wins,
            'platformStats' => $platformStats->summary(),
            'initialLeaderboard' => $leaderboardService->weeklyTopWinners(),
            'initialJackpots' => $jackpotStats->list(),
        ]));
    }

    /**
     * Página pública "Verificar mi cartón" — buscador por serial + teléfono,
     * sin sesión (ver App\Http\Controllers\Platform\BingoCardVerifyController).
     */
    public function verifyCard(): Response
    {
        return Inertia::render('Public/VerifyCard');
    }

    /**
     * Vitrina pública de salas de Bingo (sin sesión) — mismo catálogo/métricas
     * que el lobby autenticado (venta, pozo, top de compras) pero sin botón
     * de compra real: el CTA lleva a login/registro. Decisión de producto:
     * no exponer compra sin cuenta (KYC/fraude, ver análisis de riesgo).
     */
    public function gameRoomsShowcase(
        \App\Domains\Games\Bingo\Services\Room\BingoRoomContextService $contextService,
        JackpotPublicStatsService $jackpotStats,
    ): Response {
        $dto = $contextService->publicShowcase();

        return Inertia::render('Public/GameRoomsShowcase', array_merge($dto->toView(), [
            'initialJackpots' => $jackpotStats->list(),
        ]));
    }
}

<?php

use App\Http\Controllers\PublicController;
use App\Http\Controllers\Api\UserLevelController;
use App\Http\Controllers\Platform\BingoCardVerifyController;
use App\Http\Controllers\Platform\PlatformStatsController;
use App\Http\Controllers\Platform\WinEventController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
| Rutas accesibles sin autenticación para landing, información pública y API públicas.
*/

// ====================================
// RUTAS PÚBLICAS
// ====================================

/**
 * Landing pública — puerta de entrada del sitio (sin sesión). Muestra
 * salas destacadas, ganancias recientes y acceso al verificador de cartón;
 * login/registro viven en sus propias rutas. Ver PublicController::root().
 */
Route::get('/', [PublicController::class, 'root'])->name('root')->defaults('inertiaOnly', true);
Route::get('getInfo/{page}', [PublicController::class, 'info'])->name('info');

/**
 * Página pública "Verificar mi cartón" — sin sesión, como un ticket físico.
 * inertiaOnly=true: usa components.layouts.inertia (sin sidebar/navbar de
 * AdminLTE, pensados para usuario logueado — ver HandleInertiaRequests::rootView()).
 */
Route::get('/verify-card', [PublicController::class, 'verifyCard'])->name('verify-card')->defaults('inertiaOnly', true);

/**
 * Vitrina pública de salas de Bingo — sin sesión, catálogo + métricas en
 * vivo (venta, pozo, top de compras), sin compra real (CTA a login/registro).
 */
Route::get('/game-rooms', [PublicController::class, 'gameRoomsShowcase'])->name('game-rooms.showcase')->defaults('inertiaOnly', true);

/**
 * Top de compradores de una sala, para la vitrina pública — mismo dato que
 * bingo-game-rooms.top-buyers (autenticado), sin datos sensibles (solo
 * nombre/avatar/cantidad de cartones), por eso se expone también sin sesión.
 */
Route::get('/game-rooms/{room}/top-buyers', [\App\Http\Controllers\Games\Bingo\Room\BingoRoomController::class, 'topBuyers'])
    ->middleware('throttle:30,1')
    ->name('game-rooms.showcase.top-buyers');

/**
 * Health check para monitoreo externo (uptime monitors, load balancers).
 * Sin autenticación — no expone datos, solo disponibilidad de dependencias
 * críticas. Verifica DB y cache; si alguna falla responde 503.
 */
Route::get('/health', function () {
    $checks = [];
    $healthy = true;

    try {
        DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (Throwable) {
        $checks['database'] = 'error';
        $healthy = false;
    }

    try {
        $probeKey = 'health-check:probe';
        Cache::put($probeKey, true, 5);
        $checks['cache'] = Cache::get($probeKey) === true ? 'ok' : 'error';
        $healthy = $healthy && $checks['cache'] === 'ok';
    } catch (Throwable) {
        $checks['cache'] = 'error';
        $healthy = false;
    }

    return response()->json([
        'status' => $healthy ? 'ok' : 'error',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $healthy ? 200 : 503);
})->name('health');

// ====================================
// API PÚBLICAS
// ====================================
Route::prefix('api')->group(function () {
    // Sistema de Niveles - Rutas Públicas
    Route::get('/leaderboard', [UserLevelController::class, 'getLeaderboard'])->name('api.leaderboard');
    Route::post('/calculate-reward', [UserLevelController::class, 'calculateXpReward'])->name('api.calculate-reward');

    // Feed de ganancias en vivo (seed inicial para el ticker, cualquier juego/premio)
    Route::get('/win-events/recent', [WinEventController::class, 'recent'])->name('api.win-events.recent');

    // Ranking semanal de mayores ganadores en dinero (sección "Ganadores" de la landing)
    Route::get('/win-events/leaderboard', [WinEventController::class, 'leaderboard'])
        ->middleware('throttle:30,1')
        ->name('api.win-events.leaderboard');

    // Métricas públicas de la plataforma (prueba social del hero de la landing)
    Route::get('/platform/stats', [PlatformStatsController::class, 'index'])
        ->middleware('throttle:30,1')
        ->name('api.platform.stats');

    // Verificación pública de un cartón de Bingo por serial + últimos 4 del teléfono
    Route::post('/bingo/verify-card', [BingoCardVerifyController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('api.bingo.verify-card');
});

// API externa para integraciones (POS físico, proveedores externos) — requiere token con scope 'win-events.report'
Route::middleware('publicToken:win-events.report')->prefix('api/external')->group(function () {
    Route::post('/win-events', [WinEventController::class, 'reportExternal'])->name('api.external.win-events.report');
});

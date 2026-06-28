<?php

use App\Http\Controllers\PublicController;
use App\Http\Controllers\Api\UserLevelController;
use App\Http\Controllers\Platform\WinEventController;
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
Route::get('/', [PublicController::class, 'root'])->name('root')->defaults('inertiaOnly', true);
Route::get('getInfo/{page}', [PublicController::class, 'info'])->name('info');

// ====================================
// API PÚBLICAS
// ====================================
Route::prefix('api')->group(function () {
    // Sistema de Niveles - Rutas Públicas
    Route::get('/leaderboard', [UserLevelController::class, 'getLeaderboard'])->name('api.leaderboard');
    Route::post('/calculate-reward', [UserLevelController::class, 'calculateXpReward'])->name('api.calculate-reward');

    // Feed de ganancias en vivo (seed inicial para el ticker, cualquier juego/premio)
    Route::get('/win-events/recent', [WinEventController::class, 'recent'])->name('api.win-events.recent');
});

// API externa para integraciones (POS físico, proveedores externos) — requiere token con scope 'win-events.report'
Route::middleware('publicToken:win-events.report')->prefix('api/external')->group(function () {
    Route::post('/win-events', [WinEventController::class, 'reportExternal'])->name('api.external.win-events.report');
});

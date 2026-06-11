<?php

use App\Http\Controllers\PublicController;
use App\Http\Controllers\Api\UserLevelController;
use App\Http\Controllers\Platform\LiveWinsController;
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

    // Feed de ganadores en vivo (seed inicial para el ticker)
    Route::get('/live-wins/recent', [LiveWinsController::class, 'recent'])->name('api.live-wins.recent');
});

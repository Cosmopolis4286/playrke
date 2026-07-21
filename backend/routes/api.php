<?php

use App\Http\Controllers\Api\UserLevelController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - PlayRKE
|--------------------------------------------------------------------------
| Autenticación real de la app: rutas web (routes/auth.php,
| AuthenticatedSessionController) y sesión de Laravel — no este archivo.
| Este archivo solo expone endpoints puramente API (sanctum/sin sesión).
*/

//
// USER LEVEL ROUTES
//
Route::middleware('auth:sanctum')->prefix('user-level')->group(function () {
    Route::get('/', [UserLevelController::class, 'getUserLevel'])->name('api.user-level.get');
    Route::post('/add-xp', [UserLevelController::class, 'addXp'])->name('api.user-level.add-xp');
    Route::post('/remove-xp', [UserLevelController::class, 'removeXp'])->name('api.user-level.remove-xp');
    Route::post('/reset', [UserLevelController::class, 'resetLevel'])->name('api.user-level.reset');
});

Route::prefix('user-level')->group(function () {
    Route::get('/leaderboard', [UserLevelController::class, 'getLeaderboard'])->name('api.user-level.leaderboard');
    Route::post('/calculate-reward', [UserLevelController::class, 'calculateXpReward'])->name('api.user-level.calculate-reward');
});

// Nota: las rutas API de Trivia (trivia.php) son cargadas por TriviaServiceProvider

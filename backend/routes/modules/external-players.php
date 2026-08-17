<?php

use App\Http\Controllers\ExternalPlayerController;
use App\Http\Controllers\ExternalPlayerGameController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Jugadores Externos
|--------------------------------------------------------------------------
| Rutas para gestionar jugadores externos
*/

// ====================================
// JUGADORES EXTERNOS
// ====================================
route_group('external-players', 'external-players.', ['auth'], ExternalPlayerController::class, [
    'check' => ['method' => 'get', 'uri' => 'check'],
]);

/*
|--------------------------------------------------------------------------
| Vista de juego PÚBLICA (sin sesión) de un ExternalPlayer
|--------------------------------------------------------------------------
| Autorización por `link` (token de 28 chars, ver ExternalPlayerGameController
| docblock) en vez de auth/policy de Laravel — un ExternalPlayer no tiene
| cuenta `User`. Deliberadamente FUERA de route_group(): esas rutas exigen
| 'auth' en la config de bingo.php/aquí mismo, y este grupo es el único caso
| público del dominio de jugadores.
*/
Route::middleware(['throttle:external-player-game'])
    ->prefix('bingo/external/{link}')
    ->name('external-players.')
    ->group(function () {
        Route::get('/', [ExternalPlayerGameController::class, 'game'])->name('game');
        Route::get('/draw-state', [ExternalPlayerGameController::class, 'drawState'])->name('draw-state');
        // Mismo sufijo 'user-cards' que bingo.user-cards (routes/modules/games/bingo.php)
        // a propósito: el frontend (useGameEndFlow, etc.) solo varía el prefijo
        // base según sea User o ExternalPlayer, no el sufijo por endpoint.
        Route::get('/user-cards', [ExternalPlayerGameController::class, 'getUserCards'])->name('user-cards');
        Route::post('/check-bingo', [ExternalPlayerGameController::class, 'checkBingo'])->name('check-bingo');
    });

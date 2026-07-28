<?php

use App\Http\Controllers\Games\GameController;
use App\Http\Controllers\Games\Common\GameModeController;
use App\Http\Controllers\Games\Common\RoomTypeController;
use Illuminate\Support\Facades\Route;

// ====================================
// JUEGOS (GAMES)
// ====================================
route_group(
    'games',
    'games.',
    ['auth'],
    GameController::class,
    [
        'index'        => ['method' => 'get', 'uri' => ''],
        'store'        => ['method' => 'post', 'uri' => ''],
        'fetchList'    => ['method' => 'get', 'uri' => 'fetch-list'],
        'show'         => ['method' => 'get', 'uri' => '{id}'],
        'update'       => ['method' => 'put', 'uri' => '{id}'],
        'delete'       => ['method' => 'put', 'uri' => '{id}/delete'],
        'toggleStatus' => ['method' => 'put', 'uri' => '{id}/status'],
    ]
);

// ====================================
// CATÁLOGOS DE JUEGO — consumidos por la pantalla con tabs de games.index:
// Modos de Juego, Tipos de Sala, Tipos de Bote. Mismo nivel de acceso que
// gestionar el catálogo de Juegos en sí (GamePolicy: superadmin, admin).
// ====================================
Route::middleware(['auth', 'role:superadmin,admin'])->group(function () {
    // Modos de juego (catálogo dinámico por juego)
    Route::get('game-modes/fetch-list',   [GameModeController::class, 'fetchList'])    ->name('game-modes.fetch-list');
    Route::get('game-modes/{id}',         [GameModeController::class, 'show'])         ->name('game-modes.show');
    Route::post('game-modes',             [GameModeController::class, 'store'])        ->name('game-modes.store');
    Route::put('game-modes/{id}',         [GameModeController::class, 'update'])       ->name('game-modes.update');
    Route::put('game-modes/{id}/delete',  [GameModeController::class, 'delete'])       ->name('game-modes.delete');
    Route::put('game-modes/{id}/status',  [GameModeController::class, 'toggleStatus']) ->name('game-modes.status');

    // Tipos de sala (catálogo global)
    Route::get('room-types/fetch-list',   [RoomTypeController::class, 'fetchList'])    ->name('room-types.fetch-list');
    Route::get('room-types/{id}',         [RoomTypeController::class, 'show'])         ->name('room-types.show');
    Route::post('room-types',             [RoomTypeController::class, 'store'])        ->name('room-types.store');
    Route::put('room-types/{id}',         [RoomTypeController::class, 'update'])       ->name('room-types.update');
    Route::put('room-types/{id}/delete',  [RoomTypeController::class, 'delete'])       ->name('room-types.delete');
    Route::put('room-types/{id}/status',  [RoomTypeController::class, 'toggleStatus']) ->name('room-types.status');
});

<?php

use App\Http\Controllers\Api\Trivia\GameController;
use App\Http\Controllers\Api\Trivia\RoomController;
use Illuminate\Support\Facades\Route;

/**
 * Rutas API del módulo Trivia.
 * 
 * Prefijo: /api/trivia
 * Middleware: auth:sanctum (ajustar según necesidad)
 */

Route::prefix('trivia')->group(function () {
    
    // Salas
    Route::prefix('rooms')->group(function () {
        Route::get('/', [RoomController::class, 'index']);
        Route::post('/', [RoomController::class, 'store']);
        Route::get('/{roomId}', [RoomController::class, 'show']);
        Route::put('/{roomId}', [RoomController::class, 'update']);
        Route::delete('/{roomId}', [RoomController::class, 'destroy']);
        
        // Acciones de sala
        Route::post('/{roomId}/join', [RoomController::class, 'join']);
        Route::post('/{roomId}/leave', [RoomController::class, 'leave']);
    });

    // Juegos
    Route::prefix('games')->group(function () {
        // Acciones
        Route::post('/start/{roomId}', [GameController::class, 'start']);
        Route::post('/{gameId}/next', [GameController::class, 'nextQuestion']);
        Route::post('/{gameId}/answer', [GameController::class, 'submitAnswer']);
        Route::post('/{gameId}/finish', [GameController::class, 'finish']);
        Route::post('/{gameId}/cancel', [GameController::class, 'cancel']);
        
        // Consultas
        Route::get('/{gameId}', [GameController::class, 'show']);
        Route::get('/{gameId}/question', [GameController::class, 'currentQuestion']);
        Route::get('/{gameId}/leaderboard', [GameController::class, 'leaderboard']);
    });

});

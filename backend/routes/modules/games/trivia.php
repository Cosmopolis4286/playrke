<?php

/*
|--------------------------------------------------------------------------
| Trivia Game Routes
|--------------------------------------------------------------------------
| Rutas para el juego de trivia individual y multiusuario
| Organizadas por responsabilidades siguiendo el patrón de Bingo
*/

use App\Http\Controllers\Games\Trivia\Room\TriviaRoomAdminController;
use App\Http\Controllers\Games\Trivia\Report\TriviaReportController;
use App\Http\Controllers\Games\Trivia\Game\TriviaGameController;
use App\Http\Controllers\Games\Trivia\Game\TriviaGamesController;
use App\Http\Controllers\Games\Trivia\Question\TriviaQuestionController;
use Illuminate\Support\Facades\Route;

// ====================================
// RUTAS DE REPORTES Y ESTADÍSTICAS (ADMIN)
// ====================================
Route::prefix('trivia')->name('trivia.')->middleware(['auth', 'role:admin,superadmin'])->group(function () {
    Route::get('/', [TriviaReportController::class, 'index'])->name('index');
    Route::get('/winners', [TriviaReportController::class, 'winners'])->name('winners');
    Route::get('/winners-room/{coin}', [TriviaReportController::class, 'winnersRoom'])->name('winners-room');
    Route::get('/cards-sold', [TriviaReportController::class, 'cardsSold'])->name('cards-sold');
    Route::get('/sales-report', [TriviaReportController::class, 'salesReport'])->name('sales-report');
    Route::get('/sales-report-room/{coin}', [TriviaReportController::class, 'salesReportRoom'])->name('sales-report-room');
});

// ====================================
// RUTAS DE SALAS MULTIUSUARIO (ADMIN)
// ====================================
// Nuevo sistema Domain-Driven (TriviaRoom en PostgreSQL + Redis)
Route::prefix('trivia-rooms')->name('trivia-rooms-admin.')->middleware(['auth', 'role:admin,superadmin'])->group(function () {
    Route::get('/', [TriviaRoomAdminController::class, 'index'])->name('index');
    Route::get('/create', [TriviaRoomAdminController::class, 'create'])->name('create');
    Route::post('/', [TriviaRoomAdminController::class, 'store'])->name('store');
    Route::get('/{id}', [TriviaRoomAdminController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [TriviaRoomAdminController::class, 'edit'])->name('edit');
    Route::put('/{id}', [TriviaRoomAdminController::class, 'update'])->name('update');
    Route::delete('/{id}', [TriviaRoomAdminController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/force-close', [TriviaRoomAdminController::class, 'forceClose'])->name('force-close');
});

// ====================================
// RUTAS DE PREGUNTAS - BANCO GLOBAL (ADMIN)
// roomId="all" → banco completo; roomId={id} → filtro futuro por sala
// ====================================
Route::prefix('trivia-questions')->name('trivia-questions.')->middleware(['auth', 'role:admin,superadmin'])->group(function () {
    Route::get('/room/{roomId}',                          [TriviaQuestionController::class, 'index'])->name('index');
    Route::get('/room/{roomId}/create',                   [TriviaQuestionController::class, 'create'])->name('create');
    Route::post('/room/{roomId}',                         [TriviaQuestionController::class, 'store'])->name('store');
    Route::get('/room/{roomId}/{questionId}/show',        [TriviaQuestionController::class, 'show'])->name('show');
    Route::get('/room/{roomId}/{questionId}/edit',        [TriviaQuestionController::class, 'edit'])->name('edit');
    Route::put('/room/{roomId}/{questionId}',             [TriviaQuestionController::class, 'update'])->name('update');
    Route::delete('/room/{roomId}/{questionId}',          [TriviaQuestionController::class, 'destroy'])->name('destroy');
    Route::post('/room/{roomId}/import',                  [TriviaQuestionController::class, 'import'])->name('import');
    Route::patch('/room/{roomId}/{questionId}/toggle-active', [TriviaQuestionController::class, 'toggleActive'])->name('toggle-active');
});

// ====================================
// RUTAS DE JUEGO (USUARIOS)
// ====================================
Route::prefix('trivia-game')->name('trivia-games.')->middleware(['auth'])->group(function () {
    // Salas de trivia para usuarios
    Route::get('/rooms/{coin}', [TriviaGameController::class, 'rooms'])->name('rooms');
    Route::post('/room/{id}/join', [TriviaGameController::class, 'room'])->name('join');
    Route::get('/room/{id}/in-game', [TriviaGameController::class, 'inGame'])->name('in-game');
    Route::get('/room/{id}/timer', [TriviaGameController::class, 'timer'])->name('timer');
    Route::get('/room/{id}/info', [TriviaGameController::class, 'roomInfo'])->name('room-info');
    Route::post('/room/{id}/leave', [TriviaGameController::class, 'leaveRoom'])->name('leave-room');
    
    // Trivia individual
    Route::get('/individual/{id}', [TriviaGameController::class, 'individual'])->name('individual');
    Route::post('/individual/check', [TriviaGameController::class, 'checkIndividual'])->name('individual.check');
});

// ====================================
// RUTAS PARA SIDEBAR MENÚ (NUEVAS)
// ====================================
Route::prefix('trivia')->name('trivia.')->middleware(['auth'])->group(function () {
    // Lobby - Selección de salas disponibles
    Route::get('/lobby', [TriviaGameController::class, 'lobby'])->name('lobby');

    // Sala de espera de una sala concreta
    Route::get('/room/{code}/wait', [TriviaGameController::class, 'waitingRoom'])->name('room.wait');
    
    // Alias para rooms (compatibility con sidebar) - apunta al nuevo sistema
    Route::get('/rooms', function () {
        return redirect()->route('trivia-rooms-admin.index');
    })->name('rooms.index');
    
    // Alias para questions (compatibility con sidebar)
    Route::get('/questions', function () {
        return redirect()->route('trivia-questions.index', ['roomId' => 'all']);
    })->name('questions.index');
    
    // Partidas — historial y detalle
    Route::get('/games',      [TriviaGamesController::class, 'index'])->name('games.index');
    Route::get('/games/{id}', [TriviaGamesController::class, 'show'])->name('games.show');
    
    // Leaderboard / Clasificación
    Route::get('/leaderboard', [TriviaReportController::class, 'leaderboard'])->name('leaderboard');
    
    // Estadísticas
    Route::get('/stats', [TriviaReportController::class, 'stats'])->name('stats');
});

// ====================================
// RUTAS LEGACY (MANTENER COMPATIBILIDAD)
// ====================================
Route::prefix('trivia')->name('trivia.')->middleware(['auth'])->group(function () {
    // Redirecciones para mantener compatibilidad con rutas antiguas
    Route::get('/rooms/{coin}', function ($coin) {
        return redirect()->route('trivia-games.rooms', $coin);
    });
    
    Route::get('/room/{id}', function ($id) {
        return redirect()->route('trivia-games.in-game', $id);
    });
    
    Route::get('/timer/{id}', function ($id) {
        return redirect()->route('trivia-games.timer', $id);
    });
    
    Route::get('/rm-multi/{id}', function ($id) {
        return redirect()->route('trivia-games.in-game', $id);
    });
    
    Route::get('/individual/{id}', function ($id) {
        return redirect()->route('trivia-games.individual', $id);
    });
});

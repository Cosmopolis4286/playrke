<?php

/*
|--------------------------------------------------------------------------
| Trivia Game Routes
|--------------------------------------------------------------------------
| Rutas para el juego de trivia individual y multiusuario
| Organizadas por responsabilidades siguiendo el patrón de Bingo
*/

use App\Http\Controllers\Games\Trivia\Room\TriviaRoomController;
use App\Http\Controllers\Games\Trivia\Report\TriviaReportController;
use App\Http\Controllers\Games\Trivia\Game\TriviaGameController;
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
Route::prefix('trivia-rooms')->name('trivia-rooms.')->middleware(['auth', 'role:admin,superadmin'])->group(function () {
    Route::get('/', [TriviaRoomController::class, 'index'])->name('index');
    Route::get('/create', [TriviaRoomController::class, 'create'])->name('create');
    Route::post('/', [TriviaRoomController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [TriviaRoomController::class, 'edit'])->name('edit');
    Route::put('/{id}', [TriviaRoomController::class, 'update'])->name('update');
    Route::delete('/{id}', [TriviaRoomController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/show', [TriviaRoomController::class, 'show'])->name('show');
    Route::patch('/{id}/status', [TriviaRoomController::class, 'toggleStatus'])->name('toggle-status');
});

// ====================================
// RUTAS DE PREGUNTAS (ADMIN)
// ====================================
Route::prefix('trivia-questions')->name('trivia-questions.')->middleware(['auth', 'role:admin,superadmin'])->group(function () {
    Route::get('/room/{roomId}', [TriviaQuestionController::class, 'index'])->name('index');
    Route::get('/room/{roomId}/create', [TriviaQuestionController::class, 'create'])->name('create');
    Route::post('/room/{roomId}', [TriviaQuestionController::class, 'store'])->name('store');
    Route::get('/room/{roomId}/{questionId}/show', [TriviaQuestionController::class, 'show'])->name('show');
    Route::get('/room/{roomId}/{questionId}/edit', [TriviaQuestionController::class, 'edit'])->name('edit');
    Route::put('/room/{roomId}/{questionId}', [TriviaQuestionController::class, 'update'])->name('update');
    Route::delete('/room/{roomId}/{questionId}', [TriviaQuestionController::class, 'destroy'])->name('destroy');
    Route::post('/room/{roomId}/import', [TriviaQuestionController::class, 'import'])->name('import');
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
    // Lobby - Sala de espera para jugar
    Route::get('/lobby', [TriviaGameController::class, 'lobby'])->name('lobby');
    
    // Alias para rooms (compatibility con sidebar)
    Route::get('/rooms', function () {
        return redirect()->route('trivia-rooms.index');
    })->name('rooms.index');
    
    // Alias para questions (compatibility con sidebar)
    Route::get('/questions', function () {
        return redirect()->route('trivia-questions.index', ['roomId' => 'all']);
    })->name('questions.index');
    
    // Alias para games/partidas (compatibility con sidebar)
    Route::get('/games', function () {
        return redirect()->route('trivia.index');
    })->name('games.index');
    
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

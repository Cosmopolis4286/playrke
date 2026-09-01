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
use App\Http\Controllers\Games\Trivia\Game\TriviaAnswerController;
use App\Http\Controllers\Games\Trivia\Game\TriviaGameController;
use App\Http\Controllers\Games\Trivia\Game\TriviaGamesController;
use App\Http\Controllers\Games\Trivia\Game\TriviaPauseController;
use App\Http\Controllers\Games\Trivia\Game\TriviaPowerUpController;
use App\Http\Controllers\Games\Trivia\Game\TriviaStartController;
use App\Http\Controllers\Games\Trivia\Question\TriviaQuestionController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/room/{roomId}/{questionId}/edit',        [TriviaQuestionController::class, 'edit'])->name('edit');
    Route::put('/room/{roomId}/{questionId}',             [TriviaQuestionController::class, 'update'])->name('update');
    Route::delete('/room/{roomId}/{questionId}',          [TriviaQuestionController::class, 'destroy'])->name('destroy');
    Route::post('/room/{roomId}/import',                  [TriviaQuestionController::class, 'import'])->name('import');
    Route::patch('/room/{roomId}/{questionId}/toggle-active', [TriviaQuestionController::class, 'toggleActive'])->name('toggle-active');
});

// ====================================
// RUTAS DE JUEGO (USUARIOS — solo rol player)
// 'verified': unirse a sala cobra la entrada (ver JoinRoomAction) — un
// email sin verificar no debe poder mover dinero real.
// ====================================
Route::prefix('trivia-game')->name('trivia-games.')->middleware(['auth', 'verified', 'role:player'])->group(function () {
    // Unirse a sala (nuevo sistema — solo players)
    Route::post('/room/{id}/join', [TriviaGameController::class, 'join'])->name('join');

    // Iniciar partida (solo host o admin)
    Route::post('/room/{id}/start', TriviaStartController::class)->name('room.start');

    // Enviar respuesta durante una partida activa
    Route::post('/game/{gameId}/answer', [TriviaAnswerController::class, 'store'])->name('game.answer');

    // Consumir un power-up durante una partida activa
    Route::post('/game/{gameId}/power-up', [TriviaPowerUpController::class, 'store'])->name('game.power-up');
});

// ====================================
// RUTAS PARA SIDEBAR MENÚ (NUEVAS)
// ====================================

// ── Players, admins y superadmins: lobby y sala de espera (solo observar) ─────
Route::prefix('trivia')->name('trivia.')->middleware(['auth', 'role:player,admin,superadmin'])->group(function () {
    Route::get('/lobby', [TriviaGameController::class, 'lobby'])->name('lobby');
    Route::get('/room/{code}/lobby', [TriviaGameController::class, 'waitingRoom'])->name('room.wait');
    Route::get('/room/{code}/state', [TriviaGameController::class, 'roomState'])->name('room.state');
});

// ── Todos los usuarios autenticados: estadísticas, partidas, leaderboard ──────
Route::prefix('trivia')->name('trivia.')->middleware(['auth'])->group(function () {
    // Alias para rooms (compatibility con sidebar)
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

// ── Nuevas rutas para el juego "El Poder del Saber" ───────────────────────────
Route::prefix('trivia')->name('trivia.')->middleware(['auth', 'role:player,admin,superadmin'])->group(function () {
    // Página de juego activo
    Route::get('/play/{gameId}', [TriviaGameController::class, 'play'])->name('play');

    // Página de resultados
    Route::get('/play/{gameId}/results', [TriviaGameController::class, 'playResults'])->name('play.results');
});


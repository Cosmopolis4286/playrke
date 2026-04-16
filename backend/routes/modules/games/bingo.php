<?php

use App\Http\Controllers\Games\Bingo\Config\BingoConfigController;
use App\Http\Controllers\Games\Bingo\Report\BingoReportController;
use App\Http\Controllers\Games\Bingo\Pattern\BingoPatternController;
use App\Http\Controllers\Games\Bingo\Card\BingoCardController;
use App\Http\Controllers\Games\Bingo\Game\BingoGameController;
use App\Http\Controllers\Games\Bingo\Room\BingoRoomAutomaticController;
use App\Http\Controllers\Games\Bingo\Room\BingoRoomController;
use App\Http\Controllers\Games\Bingo\Testing\BingoTestingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Sistema de Bingo
|--------------------------------------------------------------------------
| Todas las funcionalidades relacionadas con el juego de bingo:
| salas, cartones, sorteos, premios, patrones, etc.
*/

/**
 * ====================================
 * INFORMACIÓN DE SALAS DE JUEGO Y CONFIGURACIÓN
 * ====================================
 */
route_group('bingo-rooms', 'bingo-rooms.', ['auth', 'multiPermission:games.manage,games.bingo'], BingoRoomController::class, [
    'index' => ['method' => 'get', 'uri' => '', 'action' => 'index'],
    'store' => ['method' => 'post', 'uri' => '', 'action' => 'store'],
    'fetch-list' => ['method' => 'get', 'uri' => 'fetch-list', 'action' => 'fetchList'],
    'show' => ['method' => 'get', 'uri' => '{room}/show', 'action' => 'show'],
    'update' => ['method' => 'patch',  'uri' => '{room}/update', 'action' => 'update'],
    'delete' => ['method' => 'delete', 'uri' => '{room}/delete', 'action' => 'delete'],
    'destroy' => ['method' => 'delete', 'uri' => '{room}/force-delete', 'action' => 'destroy'],
    'toggle-status' => ['method' => 'patch', 'uri' => '{room}/status', 'action' => 'toggleStatus'],
    'copy' => ['method' => 'post',  'uri' => '{room}/copy', 'action' => 'copy'],
    'cancel' => ['method' => 'patch',  'uri' => '{room}/cancel', 'action' => 'cancel'],
]);

/**
 * ====================================
 * CONFIGURACIÓN
 * ====================================
 */
route_group('bingo/config', 'bingo.config.', ['auth', 'multiPermission:games.manage,games.bingo'], BingoConfigController::class, [
    'index'      => ['method' => 'get',  'uri' => '',           'action' => 'index'],
    'save-rules' => ['method' => 'post', 'uri' => 'save-rules', 'action' => 'saveRules'],
    'save-image' => ['method' => 'post', 'uri' => 'save-image', 'action' => 'saveImage'],
]);

/**
 * ====================================
 * PATRONES DE BINGO (CANTOS)
 * ====================================
 */
route_group('bingo/patterns', 'bingo-patterns.', ['auth', 'multiPermission:games.manage,games.bingo'], BingoPatternController::class, [
    'index' => ['method' => 'get', 'uri' => '', 'action' => 'index'],
    'store' => ['method' => 'post', 'uri' => '', 'action' => 'store'],
    'fetch-list' => ['method' => 'get', 'uri' => 'fetch-list', 'action' => 'fetchList'],
    'show' => ['method' => 'get', 'uri' => '{pattern}/show', 'action' => 'show'],
    'update' => ['method' => 'patch',  'uri' => '{pattern}/update', 'action' => 'update'],
    'delete' => ['method' => 'delete', 'uri' => '{pattern}/delete', 'action' => 'delete'],
    'destroy' => ['method' => 'delete', 'uri' => '{pattern}/force-delete', 'action' => 'destroy'],
    'toggle-status' => ['method' => 'patch', 'uri' => '{pattern}/status', 'action' => 'toggleStatus'],
]);

/**
 * ====================================
 * SALAS AUTOMÁTICAS
 * ====================================
 */
route_group('bingo-automatic-rooms', 'bingo-automatic-rooms.', ['auth', 'multiPermission:games.manage,games.bingo'], BingoRoomAutomaticController::class, [
    'index' => ['method' => 'get', 'uri' => '', 'action' => 'index'],
    'store' => ['method' => 'post', 'uri' => '', 'action' => 'store'],
    'fetch-list' => ['method' => 'get', 'uri' => 'fetch-list', 'action' => 'fetchList'],
    'show' => ['method' => 'get', 'uri' => '{room}/show', 'action' => 'show'],
    'update' => ['method' => 'patch',  'uri' => '{room}/update', 'action' => 'update'],
    'delete' => ['method' => 'delete', 'uri' => '{room}/delete', 'action' => 'delete'],
    'destroy' => ['method' => 'delete', 'uri' => '{room}/force-delete', 'action' => 'destroy'],
    'toggle-status' => ['method' => 'patch', 'uri' => '{room}/status', 'action' => 'toggleStatus'],
    'copy' => ['method' => 'post',  'uri' => '{room}/copy', 'action' => 'copy'],
    'execute-job'   => ['method' => 'get', 'uri' => 'execute-job', 'action' => 'executeJob'],
]);

/**
 * ====================================
 * OPERACIONES CARTONES (PARA JUGADORES)
 * ====================================
 */
route_group('bingo-card', 'bingo-card.', ['auth', 'multiPermission:games.bingo,games.bingo.game_rooms'], BingoCardController::class, [
    'view-generate-card' => ['method' => 'get', 'uri' => 'generate', 'action' => 'viewGenerateCard'],
    'generate-card-store' => ['method' => 'post', 'uri' => '{room}/generate', 'action' => 'generateCardStore'],
    'fetch-room' => ['method' => 'get', 'uri' => 'fetch-room', 'action' => 'fetchRoom'],
    'available-countries' => ['method' => 'get', 'uri' => '{room}/available-countries', 'action' => 'availableCountries'],
    'card-price'          => ['method' => 'get', 'uri' => '{room}/card-price',           'action' => 'cardPrice'],
]);

/**
 * ====================================
 * SALAS DE JUEGO
 * ====================================
 */
route_group('bingo/game-rooms', 'bingo-game-rooms.', ['auth', 'multiPermission:games.bingo,games.bingo.game_rooms'], BingoRoomController::class, [
    'index' => ['method' => 'get',  'uri' => '',           'action' => 'viewGameRooms'],
    'buy-from-rooms'  => ['method' => 'post', 'uri' => '{room}/buy', 'action' => 'buyFromGameRooms'],
    'player-buy'       => ['method' => 'post', 'uri' => '{room}/player-buy', 'action' => 'playerBuyFromGameRooms'],
    'check-user-role'  => ['method' => 'get',  'uri' => 'check-user-role', 'action' => 'checkUserRole'],
    'player-card-price' => ['method' => 'get', 'uri' => '{room}/player-card-price', 'action' => 'playerCardPrice'],
]);

/**
 * ====================================
 * JUEGO DE BINGO
 * ====================================
 */
route_group('bingo', 'bingo.', ['auth'], BingoGameController::class, [
    'lobby'       => ['method' => 'get',  'uri' => '{room}/lobby',              'action' => 'lobby'],
    'game'        => ['method' => 'get',  'uri' => '{room}/game',              'action' => 'inGame'],
    'user-cards'  => ['method' => 'get',  'uri' => '{room}/user-cards',  'action' => 'getUserCards'],
    'check-bingo' => ['method' => 'post', 'uri' => '{room}/check-bingo', 'action' => 'checkBingo'],
]);

// Solo disponible en entorno local — utilidades de desarrollo
if (app()->environment('local')) {
    Route::post('bingo/{room}/dev-reset-time', [\App\Http\Controllers\Games\Bingo\Game\BingoGameController::class, 'devResetStartTime'])
        ->middleware(['auth'])
        ->name('bingo.dev-reset-time');

    Route::post('bingo/{room}/dev-start-draw', [\App\Http\Controllers\Games\Bingo\Game\BingoGameController::class, 'startDraw'])
        ->middleware(['auth'])
        ->name('bingo.dev-start-draw');
}

/**
 * ====================================
 * TESTING Y DESARROLLO
 * ====================================
 */
route_group('bingo-test', 'bingo-test.', ['auth', 'multiPermission:superadmin.access'], BingoTestingController::class, [
    'generate-draw' => ['method' => 'get', 'uri' => 'generate-draw', 'action' => 'generateDraw']
]);

/**
 * ====================================
 * REPORTES Y ESTADÍSTICAS
 * ====================================
 */
route_group('bingo', 'bingo.', [], BingoReportController::class, [
    'cards-sold'        => ['method' => 'get', 'uri' => 'cards-sold', 'action' => 'cardsSold'],
    'sales-report'      => ['method' => 'get', 'uri' => 'sales-report', 'action' => 'salesReport'],
    'sales-report-room' => ['method' => 'get', 'uri' => 'sales-report/room/{room}', 'action' => 'salesReportByRoom'],
    'winners'           => ['method' => 'get', 'uri' => 'winners', 'action' => 'winners'],
    'winners-room'      => ['method' => 'get', 'uri' => 'winners/room/{room}'],
]);

 // 'game-info'          => ['method' => 'get', 'uri' => 'show-game-info', 'action' => 'showGameInfo'],
    // 'encrypted-info'     => ['method' => 'get', 'uri' => 'info-encrypted/{id}/{creatorUser}/{currency}'],
    // 'public-room-info'   => ['method' => 'get', 'uri' => 'public-room-info'],
    // 'pending-payments'   => ['method' => 'get', 'uri' => 'public-room-pending-payments'],

/**
 * ====================================
 * BINGO CASH (JUEGO PÚBLICO)
 * ====================================
 */
// route_group('bingocash', 'bingocash.', [], BingoReportController::class, [
//     'out-game'        => ['method' => 'get',  'uri' => 'game-room/{uuidCode}/{room?}/{currency?}'],
//     'generate-card' => ['method' => 'post', 'uri' => 'generate-card/store'],
//     'get-draw'        => ['method' => 'get',  'uri' => 'get-draw/{room}'],
//     'get-chips'       => ['method' => 'get',  'uri' => 'get/chips/{room}'],
//     'get-result'      => ['method' => 'get',  'uri' => 'get/result/{room}/{value}/{column?}'],
// ]);

/**
 * ====================================
 * RUTAS ESPECIALES
 * ====================================
 */
// Route::get('bingo/view-ctn/{ctnId}', [BingoReportController::class, 'viewCtn'])->name('bingo.view-carton');
// Route::get('bingo/{roomType}/{creatorType}/{creatorId?}/{currency?}', [BingoReportController::class, 'handleGameRoomSummary'])->name('bingo-rooms.index');
// Route::get('{customer}/{userToken}/room/{room}', [BingoReportController::class, 'inGameCustomer'])->name('bingo.game-customer');
// Route::get('game/{customer}/room/{room}/{currency}', [BingoReportController::class, 'outGameCustomer'])->name('bingo.out-game-customer');

// Ruta especial de sesión
Route::get('bingo/k3yg4m3/play-rke', function () {
    return response()->json(['key' => session('r4m60nk3y')]);
})->name('bingo.session-key');

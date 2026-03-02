<?php

use App\Http\Controllers\Games\Bingo\Config\BingoConfigController;
use App\Http\Controllers\Games\Bingo\GameBingoController;
use App\Http\Controllers\Games\Bingo\Pattern\BingoPatternController;
use App\Http\Controllers\Games\Bingo\Card\BingoCardController;
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
route_group('bingo-rooms', 'bingo-rooms.', ['auth', 'role:admin,commerce'], BingoRoomController::class, [
    'index' => ['method' => 'get', 'uri' => '', 'action' => 'index'],
    'store' => ['method' => 'post', 'uri' => '', 'action' => 'store'],
    'fetch-list' => ['method' => 'get', 'uri' => 'fetch-list', 'action' => 'fetchList'],
    'show' => ['method' => 'get', 'uri' => '{room}/show', 'action' => 'show'],
    'update' => ['method' => 'patch',  'uri' => '{room}/update', 'action' => 'update'],
    'delete' => ['method' => 'delete', 'uri' => '{room}/delete', 'action' => 'delete'],
    'destroy' => ['method' => 'delete', 'uri' => '{room}/force-delete', 'action' => 'destroy'],
    'toggle-status' => ['method' => 'patch', 'uri' => '{room}/status', 'action' => 'toggleStatus'],
    'copy' => ['method' => 'post',  'uri' => '{room}/copy', 'action' => 'copy'],
    'publish' => ['method' => 'patch',  'uri' => '{room}/publish', 'action' => 'publish'],
    'cancel' => ['method' => 'patch',  'uri' => '{room}/cancel', 'action' => 'cancel'],
]);

/**
 * ====================================
 * CONFIGURACIÓN
 * ====================================
 */
route_group('bingo/config', 'bingo.config.', ['auth', 'role:admin,commerce'], BingoConfigController::class, [
    'index'      => ['method' => 'get',  'uri' => '',           'action' => 'index'],
    'save-rules' => ['method' => 'post', 'uri' => 'save-rules', 'action' => 'saveRules'],
    'save-image' => ['method' => 'post', 'uri' => 'save-image', 'action' => 'saveImage'],
]);

/**
 * ====================================
 * PATRONES DE BINGO (CANTOS)
 * ====================================
 */
route_group('bingo/patterns', 'bingo-pattern.', ['auth', 'role:admin,commerce'], BingoPatternController::class, [
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
route_group('bingo-automatic-rooms', 'bingo-automatic-rooms.', ['auth', 'role:admin,commerce'], BingoRoomAutomaticController::class, [
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
 * OPERACIONES CARTONES
 * ====================================
 */
route_group('bingo-card', 'bingo-card.', ['auth', 'role:admin,commerce,seller'], BingoCardController::class, [
    'view-generate-card' => ['method' => 'get', 'uri' => 'generate', 'action' => 'viewGenerateCard'],
    'generate-card-store' => ['method' => 'post', 'uri' => 'generate-card-store', 'action' => 'generateCardStore'],
    'available-countries' => ['method' => 'get', 'uri' => '{room}/available-countries', 'action' => 'availableCountries'],
]);

/**
 * ====================================
 * TESTING Y DESARROLLO
 * ====================================
 */
route_group('bingo-test', 'bingo-test.', ['auth', 'role:admin'], BingoTestingController::class, [
    'generate-draw' => ['method' => 'get', 'uri' => 'generate-draw', 'action' => 'generateDraw']
]);

/**
 * ====================================
 * RUTAS ADMIN Y COMMERCE (MULTI-MIDDLEWARE)
 * ====================================
 */
multi_middleware_routes('bingo', 'bingo.', [
    [
        'middleware' => ['auth', 'commerce'],
        'controller' => GameBingoController::class,
        'routes' => [
            'game-rooms-commerce'          => ['method' => 'get', 'uri' => '{roomType}/game-rooms/{currency?}'],
            'prizes'                      => ['method' => 'get', 'uri' => 'prizes'],
            'sellers'                      => ['method' => 'get', 'uri' => 'sellers'],
            'getCurrencyByCountry'         => ['method' => 'get', 'uri' => 'seller/getCurrencyByCountry/{country}/{seller}'],
            'getSellerInfoByCtn'           => ['method' => 'get', 'uri' => 'seller/getSellerInfoByCtn/{ctnId}'],
            'ctnsLotPayment'               => ['method' => 'put', 'uri' => 'bingo/carton-lot/payment'],
        ]
    ]
]);

/**
 * ====================================
 * REPORTES Y ESTADÍSTICAS
 * ====================================
 */
route_group('bingo', 'bingo.', [], GameBingoController::class, [
    'sales-report'     => ['method' => 'get', 'uri' => 'sales-report/{currency?}/{dateFilter?}'],
    'sales-by-room'    => ['method' => 'get', 'uri' => 'sales-report-by-room/{room}/{currency}'],
    'winners-by-room'  => ['method' => 'get', 'uri' => 'sales-report-by-room-winners/{room}/{currency}'],
    'winners'          => ['method' => 'get', 'uri' => 'winners/{type}/{dateFilter}/{currency?}'],
    'cartons-sold'     => ['method' => 'get', 'uri' => 'cartons-sold/{currency?}/{dateFilter?}'],
]);

 // 'game-info'          => ['method' => 'get', 'uri' => 'show-game-info', 'action' => 'showGameInfo'],
    // 'encrypted-info'     => ['method' => 'get', 'uri' => 'info-encrypted/{id}/{creatorUser}/{currency}'],
    // 'public-room-info'   => ['method' => 'get', 'uri' => 'public-room-info'],
    // 'pending-payments'   => ['method' => 'get', 'uri' => 'public-room-pending-payments'],
    // 'rounds-config'      => ['method' => 'get', 'uri' => 'rounds-config/{room}/{type}'],
    // 'datetime-rooms'     => ['method' => 'get', 'uri' => 'datetime-rooms/{creatorUser}/{player}'],
    // 'price-currency'     => ['method' => 'get', 'uri' => 'price-currency/{creation}/{creatorType}/{creatorId}/{room?}'],

/**
 * ====================================
 * BINGO CASH (JUEGO PÚBLICO)
 * ====================================
 */
route_group('bingocash', 'bingocash.', [], GameBingoController::class, [
    'out-game'        => ['method' => 'get',  'uri' => 'game-room/{uuidCode}/{room?}/{currency?}'],
    'generate-card' => ['method' => 'post', 'uri' => 'generate-card/store'],
    'get-draw'        => ['method' => 'get',  'uri' => 'get-draw/{room}'],
    'get-chips'       => ['method' => 'get',  'uri' => 'get/chips/{room}'],
    'get-result'      => ['method' => 'get',  'uri' => 'get/result/{room}/{value}/{column?}'],
]);

/**
 * ====================================
 * RUTAS ESPECIALES
 * ====================================
 */
// Route::get('bingo/view-ctn/{ctnId}', [GameBingoController::class, 'viewCtn'])->name('bingo.view-carton');
// Route::get('bingo/{roomType}/{creatorType}/{creatorId?}/{currency?}', [GameBingoController::class, 'handleGameRoomSummary'])->name('bingo-rooms.index');
// Route::get('{customer}/{userToken}/room/{room}', [GameBingoController::class, 'inGameCustomer'])->name('bingo.in-game-customer');
// Route::get('game/{customer}/room/{room}/{currency}', [GameBingoController::class, 'outGameCustomer'])->name('bingo.out-game-customer');

// Ruta especial de sesión
Route::get('bingo/k3yg4m3/play-rke', function () {
    return response()->json(['key' => session('r4m60nk3y')]);
})->name('bingo.session-key');

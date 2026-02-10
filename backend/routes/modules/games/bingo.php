<?php

use App\Http\Controllers\Games\Bingo\BingoController;
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
 * RUTAS ADMIN Y COMMERCE (MULTI-MIDDLEWARE)
 * ====================================
 */
multi_middleware_routes('bingo', 'bingo.', [
    [
        'middleware' => ['auth', 'admin'],
        'controller' => BingoController::class,
        'routes' => [
            'game-rooms-admin' => ['method' => 'get', 'uri' => 'game-rooms/admin/{user}/{currency?}'],
            'activate-winner'  => ['method' => 'post', 'uri' => 'activate-winner'],
            'create'           => ['method' => 'post', 'uri' => 'create'],
            'update'           => ['method' => 'put',  'uri' => 'update/{id}'],
            'delete'           => ['method' => 'delete', 'uri' => 'delete/{id}'],
            'changeStatus'    => ['method' => 'put',  'uri' => 'status/{id}'],
            'replay'           => ['method' => 'put',  'uri' => 'replay/{id}'],
            'publish'          => ['method' => 'put',  'uri' => 'publish/{id}'],
            'refund'           => ['method' => 'put',  'uri' => 'refund/{id}'],
            'copy'             => ['method' => 'put',  'uri' => 'copy/{id}'],
        ]
    ],
    [
        'middleware' => ['auth', 'commerce'],
        'controller' => BingoController::class,
        'routes' => [
            'game-rooms-commerce'          => ['method' => 'get', 'uri' => '{roomType}/game-rooms/{currency?}'],
            'rewards'                      => ['method' => 'get', 'uri' => 'rewards'],
            'sellers'                      => ['method' => 'get', 'uri' => 'sellers'],
            'getCurrencyByCountry'         => ['method' => 'get', 'uri' => 'seller/getCurrencyByCountry/{country}/{seller}'],
            'getSellerInfoByCtn'           => ['method' => 'get', 'uri' => 'seller/getSellerInfoByCtn/{ctnId}'],
            'ctnsLotPayment'               => ['method' => 'put', 'uri' => 'bingo/carton-lot/payment'],
        ]
    ]
]);

/**
 * ====================================
 * INFORMACIÓN Y CONFIGURACIÓN
 * ====================================
 */
route_group('bingo', 'bingo.', [], BingoController::class, [
    'game-rooms-summary' => ['method' => 'get', 'uri' => 'game-rooms-summary', 'action' => 'gameRoomsSummary'],
    'form'               => ['method' => 'get', 'uri' => 'form/{roomId?}', 'action' => 'formData'],
    'game-info'          => ['method' => 'get', 'uri' => 'show-game-info', 'action' => 'showGameInfo'],
    'configuration'      => ['method' => 'get', 'uri' => 'configuration/{userToken?}'],
    'patterns'           => ['method' => 'get', 'uri' => 'configuration/patterns/{userToken?}'],
    'encrypted-info'     => ['method' => 'get', 'uri' => 'info-encrypted/{id}/{creatorUser}/{currency}'],
    'public-room-info'   => ['method' => 'get', 'uri' => 'public-room-info'],
    'pending-payments'   => ['method' => 'get', 'uri' => 'public-room-pending-payments'],
    'rounds-config'      => ['method' => 'get', 'uri' => 'rounds-config/{room}/{type}'],
    'datetime-rooms'     => ['method' => 'get', 'uri' => 'datetime-rooms/{creatorUser}/{player}'],
    'price-currency'     => ['method' => 'get', 'uri' => 'price-currency/{creation}/{creatorType}/{creatorId}/{room?}'],
]);

/**
 * ====================================
 * OPERACIONES DE JUEGO
 * ====================================
 */
route_group('bingo', 'bingo.', [], BingoController::class, [
    'call-number'      => ['method' => 'post', 'uri' => 'room/{roomId}/call-number'],
    'participate'      => ['method' => 'put',  'uri' => 'participate/public-room/{id?}/{getOut?}'],
    'notify-payment'   => ['method' => 'post', 'uri' => 'carton/notify/payment/win-ctn'],
    'handle-rules'     => ['method' => 'put',  'uri' => 'handle-rules/{id?}'],
]);

/**
 * ====================================
 * REPORTES Y ESTADÍSTICAS
 * ====================================
 */
route_group('bingo', 'bingo.', [], BingoController::class, [
    'sales-report'     => ['method' => 'get', 'uri' => 'sales-report/{currency?}/{dateFilter?}'],
    'sales-by-room'    => ['method' => 'get', 'uri' => 'sales-report-by-room/{room}/{currency}'],
    'winners-by-room'  => ['method' => 'get', 'uri' => 'sales-report-by-room-winners/{room}/{currency}'],
    'winners'          => ['method' => 'get', 'uri' => 'winners/{type}/{dateFilter}/{currency?}'],
    'cartons-sold'     => ['method' => 'get', 'uri' => 'cartons-sold/{currency?}/{dateFilter?}'],
]);

/**
 * ====================================
 * PATRONES Y SALAS AUTOMÁTICAS
 * ====================================
 */
route_group('bingo-patterns', 'bingo-pattern.', [], BingoController::class, [
    'show'    => ['method' => 'get', 'uri' => '{id}'],
    'handle'  => ['method' => 'put', 'uri' => 'handler/{id?}/{action?}'],
]);

route_group('bingo-automatic-room', 'bingo.automatic.', ['auth'], BingoController::class, [
    'show'          => ['method' => 'get', 'uri' => '{id}'],
    'create'        => ['method' => 'post', 'uri' => ''],
    'update'        => ['method' => 'put', 'uri' => '{id}'],
    'changeStatus' => ['method' => 'put', 'uri' => '{id}/status'],
    'destroy'       => ['method' => 'put', 'uri' => '{id}/delete'],
    'copy'          => ['method' => 'put', 'uri' => '{id}/copy'],
    'execute-job'   => ['method' => 'get', 'uri' => 'execute-job/{creatorType}/{creatorId}'],
]);

/**
 * ====================================
 * BINGO CASH (JUEGO PÚBLICO)
 * ====================================
 */
route_group('bingocash', 'bingocash.', [], BingoController::class, [
    'out-game'        => ['method' => 'get',  'uri' => 'game-room/{uuidCode}/{room?}/{currency?}'],
    'generate-carton' => ['method' => 'post', 'uri' => 'carton/generate/store'],
    'get-draw'        => ['method' => 'get',  'uri' => 'get-draw/{room}'],
    'get-chips'       => ['method' => 'get',  'uri' => 'get/chips/{room}'],
    'get-result'      => ['method' => 'get',  'uri' => 'get/result/{room}/{value}/{column?}'],
]);

/**
 * ====================================
 * RUTAS ESPECIALES
 * ====================================
 */
// Route::get('bingo/view-ctn/{ctnId}', [BingoController::class, 'viewCtn'])->name('bingo.view-carton');
// Route::get('bingo/{roomType}/{creatorType}/{creatorId?}/{currency?}', [BingoController::class, 'handleGameRoomSummary'])->name('bingo.game-rooms-summary');
// Route::get('{customer}/{userToken}/room/{room}', [BingoController::class, 'inGameCustomer'])->name('bingo.in-game-customer');
// Route::get('game/{customer}/room/{room}/{currency}', [BingoController::class, 'outGameCustomer'])->name('bingo.out-game-customer');

// Ruta especial de sesión
Route::get('bingo/k3yg4m3/play-rke', function () {
    return response()->json(['key' => session('r4m60nk3y')]);
})->name('bingo.session-key');

// Testing y desarrollo
Route::post('bingo/test/regenerate-draw', [BingoController::class, 'regenerateDraw'])->name('bingo.test.regenerate-draw');

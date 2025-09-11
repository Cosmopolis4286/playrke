<?php

use App\Http\Controllers\{BingoController, SellerController};

require_once app_path('Helpers/RouteHelper.php');

/*
|--------------------------------------------------------------------------
| Rutas del Sistema de Bingo
|--------------------------------------------------------------------------
| Todas las funcionalidades relacionadas con el juego de bingo:
| salas, cartones, sorteos, premios, patrones, etc.
*/

// ====================================
// GESTIÓN DE SALAS DE BINGO (ADMIN)
// ====================================
multi_middleware_routes('bingo', 'bingo.', [
    [
        'middleware' => ['auth', 'admin'],
        'controller' => BingoController::class,
        'routes' => [
            'game-rooms-admin' => ['method' => 'get', 'uri' => 'game-rooms/admin/{user}/{currency?}'],
            'activate-winner' => ['method' => 'post', 'uri' => 'activate-winner'],
        ]
    ],
    [
        'middleware' => ['auth', 'commerce_id'],
        'controller' => BingoController::class,
        'routes' => [
            'game-rooms-commerce' => ['method' => 'get', 'uri' => '{roomType}/game-rooms/{currency?}'],
        ]
    ]
]);

// ====================================
// CRUD DE BINGO (SIN MIDDLEWARE ESPECÍFICO)
// ====================================
route_group('bingo', 'bingo.', [], BingoController::class, [
    'create' => ['method' => 'post', 'uri' => 'create'],
    'show' => ['method' => 'get', 'uri' => '{id}/{startTime?}'],
    'update' => ['method' => 'put', 'uri' => 'update/{id}'],
    'destroy' => ['method' => 'delete', 'uri' => 'delete/{id}'],
    'change-status' => ['method' => 'put', 'uri' => 'status/{id}'],
    'replay' => ['method' => 'put', 'uri' => 'replay/{id}'],
    'publish' => ['method' => 'put', 'uri' => 'publish/{id}'],
    'refund' => ['method' => 'put', 'uri' => 'refund/{id}'],
    'copy' => ['method' => 'put', 'uri' => 'copy/{id}'],
]);

// ====================================
// INFORMACIÓN Y CONFIGURACIÓN
// ====================================
route_group('bingo', 'bingo.', [], BingoController::class, [
    'form' => ['method' => 'get', 'uri' => 'form/{roomId?}'],
    'game-info' => ['method' => 'get', 'uri' => 'show-game-info'],
    'configuration' => ['method' => 'get', 'uri' => 'configuration/{userToken?}'],
    'patterns' => ['method' => 'get', 'uri' => 'configuration/patterns/{userToken?}'],
    'encrypted-info' => ['method' => 'get', 'uri' => 'info-encrypted/{id}/{creatorUser}/{currency}'],
    'public-room-info' => ['method' => 'get', 'uri' => 'public-room-info'],
    'pending-payments' => ['method' => 'get', 'uri' => 'public-room-pending-payments'],
    'rounds-config' => ['method' => 'get', 'uri' => 'rounds-config/{room}/{type}'],
    'datetime-rooms' => ['method' => 'get', 'uri' => 'datetime-rooms/{creatorUser}/{player}'],
    'price-currency' => ['method' => 'get', 'uri' => 'price-currency/{creation}/{creatorType}/{creatorId}/{room?}'],
]);

// ====================================
// OPERACIONES DE JUEGO
// ====================================
route_group('bingo', 'bingo.', [], BingoController::class, [
    'call-number' => ['method' => 'post', 'uri' => 'room/{roomId}/call-number'],
    'participate' => ['method' => 'put', 'uri' => 'participate/public-room/{id?}/{getOut?}'],
    'notify-payment' => ['method' => 'post', 'uri' => 'carton/notify/payment/win-ctn'],
    'handle-rules' => ['method' => 'put', 'uri' => 'handle-rules/{id?}'],
]);

// ====================================
// REPORTES Y ESTADÍSTICAS
// ====================================
route_group('bingo', 'bingo.', [], BingoController::class, [
    'sales-report' => ['method' => 'get', 'uri' => 'sales-report/{currency?}/{dateFilter?}'],
    'sales-by-room' => ['method' => 'get', 'uri' => 'sales-report-by-room/{room}/{currency}'],
    'winners-by-room' => ['method' => 'get', 'uri' => 'sales-report-by-room-winners/{room}/{currency}'],
    'winners' => ['method' => 'get', 'uri' => 'winners/{type}/{dateFilter}/{currency?}'],
    'cartons-sold' => ['method' => 'get', 'uri' => 'cartons-sold/{currency?}/{dateFilter?}'],
]);

// ====================================
// SISTEMA DE PATRONES
// ====================================
route_group('bingo-patterns', 'bingo-pattern.', [], BingoController::class, [
    'show' => ['method' => 'get', 'uri' => '{id}'],
    'handle' => ['method' => 'put', 'uri' => 'handler/{id?}/{action?}'],
]);

// ====================================
// SALAS AUTOMÁTICAS
// ====================================
route_group('bingo-automatic-room', 'bingo.automatic.', ['auth'], BingoController::class, [
    'show' => ['method' => 'get', 'uri' => '{id}'],
    'create' => ['method' => 'post', 'uri' => ''],
    'update' => ['method' => 'put', 'uri' => '{id}'],
    'change-status' => ['method' => 'put', 'uri' => '{id}/status'],
    'destroy' => ['method' => 'put', 'uri' => '{id}/delete'],
    'copy' => ['method' => 'put', 'uri' => '{id}/copy'],
    'execute-job' => ['method' => 'get', 'uri' => 'execute-job/{creatorType}/{creatorId}'],
]);

// ====================================
// BINGO CASH (JUEGO PÚBLICO)
// ====================================
route_group('bingocash', 'bingocash.', [], BingoController::class, [
    'out-game' => ['method' => 'get', 'uri' => 'game-room/{uuidCode}/{room?}/{currency?}'],
    'generate-carton' => ['method' => 'post', 'uri' => 'carton/generate/store'],
    'get-draw' => ['method' => 'get', 'uri' => 'get-draw/{room}'],
    'get-chips' => ['method' => 'get', 'uri' => 'get/chips/{room}'],
    'get-result' => ['method' => 'get', 'uri' => 'get/result/{room}/{value}/{column?}'],
]);

// ====================================
// RUTAS ESPECIALES Y UTILIDADES
// ====================================
Route::get('bingo/view-ctn/{ctnId}', [BingoController::class, 'viewCtn'])->name('bingo.view-carton');
Route::get('bingo/{roomType}/{creatorType}/{creatorId?}/{currency?}', [BingoController::class, 'handleGameRoomSummary'])->name('bingo.room-summary');
Route::get('{customer}/{userToken}/room/{room}', [BingoController::class, 'inGameCustomer'])->name('bingo.in-game-customer');
Route::get('game/{customer}/room/{room}/{currency}', [BingoController::class, 'outGameCustomer'])->name('bingo.out-game-customer');

// Ruta especial de sesión
Route::get('bingo/k3yg4m3/play-rke', function () {
    return response()->json(['key' => session('r4m60nk3y')]);
})->name('bingo.session-key');

// Testing y desarrollo
Route::post('bingo/test/regenerate-draw', [BingoController::class, 'regenerateDraw'])->name('bingo.test.regenerate-draw');
<?php

use App\Http\Controllers\Games\Bingo\Config\BingoConfigController;
use App\Http\Controllers\Games\Bingo\Report\BingoReportController;
use App\Http\Controllers\Games\Bingo\Pattern\BingoPatternController;
use App\Http\Controllers\Games\Bingo\Card\BingoCardController;
use App\Http\Controllers\Games\Bingo\Game\BingoDrawReplayController;
use App\Http\Controllers\Games\Bingo\Room\BingoRoomStatsController;
use App\Http\Controllers\Games\Bingo\Room\BingoRoomCustomCardStatsController;
use App\Http\Controllers\Games\Bingo\Game\BingoGameController;
use App\Http\Controllers\Games\Bingo\Room\BingoRoomAutomaticController;
use App\Http\Controllers\Games\Bingo\Room\BingoRoomController;
use App\Http\Controllers\Games\Bingo\History\PlayerGameHistoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Sistema de Bingo
|--------------------------------------------------------------------------
| Todas las funcionalidades relacionadas con el juego de bingo:
| salas, cartones, sorteos, premios, patrones, etc.
*/

// Alias: el frontend hardcodea /bingo/rooms → redirige a la ruta canónica
Route::middleware(['auth', 'multiPermission:games.manage,games.bingo.manage'])
    ->get('bingo/rooms', [BingoRoomController::class, 'index'])
    ->name('bingo.rooms.alias');

/**
 * ====================================
 * INFORMACIÓN DE SALAS DE JUEGO Y CONFIGURACIÓN (GESTIÓN)
 * games.bingo.manage (no games.bingo): games.bingo es "puedo jugar/comprar
 * bingo" (lo tiene hasta 'player') — usarlo aquí habría dejado la puerta de
 * CREAR/EDITAR/BORRAR salas abierta a cualquiera que solo pueda jugar.
 * games.bingo.manage es el permiso específico de gestión, ahora asignado a
 * admin/commerce/subsidiary/customer (ver RolePermissionSeeder) y exigido
 * también por BingoRoomPolicy — antes la Policy no lo consultaba en absoluto.
 * ====================================
 */
route_group('bingo-rooms', 'bingo-rooms.', ['auth', 'multiPermission:games.manage,games.bingo.manage'], BingoRoomController::class, [
    'index' => ['method' => 'get', 'uri' => '', 'action' => 'index'],
    'store' => ['method' => 'post', 'uri' => '', 'action' => 'store'],
    'fetch-list' => ['method' => 'get', 'uri' => 'fetch-list', 'action' => 'fetchList'],
    'summary-data' => ['method' => 'get', 'uri' => 'summary-data', 'action' => 'summaryData'],
    'show' => ['method' => 'get', 'uri' => '{room}/show', 'action' => 'show'],
    'update' => ['method' => 'patch',  'uri' => '{room}/update', 'action' => 'update'],
    'delete' => ['method' => 'delete', 'uri' => '{room}/delete', 'action' => 'delete'],
    'destroy' => ['method' => 'delete', 'uri' => '{room}/force-delete', 'action' => 'destroy'],
    'toggle-status' => ['method' => 'patch', 'uri' => '{room}/status', 'action' => 'toggleStatus'],
    'copy' => ['method' => 'post',  'uri' => '{room}/copy', 'action' => 'copy'],
    'cancel' => ['method' => 'patch',  'uri' => '{room}/cancel', 'action' => 'cancel'],
    'activate-now' => ['method' => 'patch', 'uri' => '{room}/activate-now', 'action' => 'activateNow'],
    'schedule-start' => ['method' => 'patch', 'uri' => '{room}/schedule-start', 'action' => 'scheduleStart'],
    'export' => ['method' => 'get', 'uri' => 'export', 'action' => 'export'],
]);

// Lista de invitados de salas VIP — mismo permiso de gestión que
// bingo-rooms.*; la policy (update) limita a creador de la sala o admin.
Route::middleware(['auth', 'multiPermission:games.manage,games.bingo.manage'])
    ->prefix('bingo-rooms/{room}/invitations')
    ->name('bingo-rooms.invitations.')
    ->controller(\App\Http\Controllers\Games\Bingo\Room\BingoRoomInvitationController::class)
    ->group(function () {
        Route::get('', 'index')->name('index');
        Route::post('', 'store')->name('store');
        // Antes de {invitation}: si no, "requirements" se tomaría como un id.
        Route::patch('requirements', 'updateRequirements')->name('requirements');
        Route::delete('{invitation}', 'destroy')->name('destroy');
    });

// Replay de fichas cantadas (auditoría post-sorteo) — mismo permiso de
// gestión que bingo-rooms.* de arriba, no games.bingo (jugar): esta info vive
// dentro de la pantalla de administración de una sala, no algo que un
// jugador común consulte.
Route::get('bingo-rooms/{room}/draw/replay', BingoDrawReplayController::class)
    ->middleware(['auth', 'multiPermission:games.manage,games.bingo.manage'])
    ->name('bingo-rooms.draw.replay');

// Estadísticas agregadas de sala (snapshot post-sorteo)
Route::get('bingo-rooms/{room}/stats', BingoRoomStatsController::class)
    ->middleware(['auth', 'multiPermission:games.manage,games.bingo.manage'])
    ->name('bingo-rooms.stats');

// Estadísticas de cartones personalizados de la sala
Route::get('bingo-rooms/{room}/cards/custom-stats', BingoRoomCustomCardStatsController::class)
    ->middleware(['auth', 'multiPermission:games.manage,games.bingo.manage'])
    ->name('bingo-rooms.cards.custom-stats');

/**
 * ====================================
 * CONFIGURACIÓN
 * Solo games.manage (admin/superadmin): reglas/imagen son configuración
 * GLOBAL del juego, no de un comercio individual — antes 'games.bingo'
 * (permiso de "puedo jugar") dejaba esto editable por cualquier
 * commerce/subsidiary/customer, mismo hueco que bingo-automatic-rooms.
 * ====================================
 */
route_group('bingo/config', 'bingo.config.', ['auth', 'multiPermission:games.manage'], BingoConfigController::class, [
    'index'      => ['method' => 'get',  'uri' => '',           'action' => 'index'],
    'save-rules' => ['method' => 'post', 'uri' => 'save-rules', 'action' => 'saveRules'],
    'save-image' => ['method' => 'post', 'uri' => 'save-image', 'action' => 'saveImage'],
]);

/**
 * ====================================
 * PATRONES DE BINGO (CANTOS)
 * Solo games.manage: catálogo GLOBAL de patrones, compartido por todas las
 * salas de todos los comercios — mismo criterio que bingo/config de arriba.
 * ====================================
 */
route_group('bingo/patterns', 'bingo-patterns.', ['auth', 'multiPermission:games.manage'], BingoPatternController::class, [
    'index'               => ['method' => 'get',    'uri' => '',                    'action' => 'index'],
    'store'               => ['method' => 'post',   'uri' => '',                    'action' => 'store'],
    'fetch-list'          => ['method' => 'get',    'uri' => 'fetch-list',          'action' => 'fetchList'],
    'show'                => ['method' => 'get',    'uri' => '{pattern}/show',      'action' => 'show'],
    'update'              => ['method' => 'patch',  'uri' => '{pattern}/update',    'action' => 'update'],
    'delete'              => ['method' => 'delete', 'uri' => '{pattern}/delete',    'action' => 'delete'],
    'destroy'             => ['method' => 'delete', 'uri' => '{pattern}/force-delete', 'action' => 'destroy'],
    'toggle-status'       => ['method' => 'patch',  'uri' => '{pattern}/status',   'action' => 'toggleStatus'],
    'bulk-toggle-status'  => ['method' => 'patch',  'uri' => 'bulk/status',        'action' => 'toggleStatusBulk'],
    'bulk-delete'         => ['method' => 'delete', 'uri' => 'bulk/delete',        'action' => 'deleteBulk'],
]);

/**
 * ====================================
 * SALAS AUTOMÁTICAS
 * Solo games.manage (admin/superadmin) — a diferencia de bingo-rooms
 * (salas manuales), estas son un recurso de PLATAFORMA sin dueño de negocio
 * (ver BingoRoomAutomaticPolicy). Antes incluía 'games.bingo' aquí, lo que
 * dejaba la ruta abierta a commerce/subsidiary/customer (games.bingo es
 * "puedo jugar", no "puedo administrar salas de plataforma") pese a que la
 * intención documentada en la Policy siempre fue admin-only.
 * ====================================
 */
route_group('bingo-automatic-rooms', 'bingo-automatic-rooms.', ['auth', 'multiPermission:games.manage'], BingoRoomAutomaticController::class, [
    'index' => ['method' => 'get', 'uri' => '', 'action' => 'index'],
    'store' => ['method' => 'post', 'uri' => '', 'action' => 'store'],
    'fetch-list' => ['method' => 'get', 'uri' => 'fetch-list', 'action' => 'fetchList'],
    'summary-data' => ['method' => 'get', 'uri' => 'summary-data', 'action' => 'summaryData'],
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
 * 'verified': generar/comprar cartones cobra dinero real — un email sin
 * verificar no debe poder mover fondos.
 * ====================================
 */
route_group('bingo-card', 'bingo-card.', ['auth', 'verified', 'multiPermission:games.bingo,games.bingo.game_rooms'], BingoCardController::class, [
    'view-generate-card' => ['method' => 'get', 'uri' => 'generate', 'action' => 'viewGenerateCard'],
    'generate-card-store' => ['method' => 'post', 'uri' => '{room}/generate', 'action' => 'generateCardStore'],
    // Punto de venta del vendedor (solo seller — ver BingoCardController::ensureSeller()).
    'pos-rooms'           => ['method' => 'get', 'uri' => 'pos/rooms',            'action' => 'rooms'],
    'pos-frequent-buyers' => ['method' => 'get', 'uri' => 'pos/frequent-buyers',  'action' => 'frequentBuyers'],
    'pos-today-sales'     => ['method' => 'get', 'uri' => 'pos/today-sales',      'action' => 'todaySales'],
    // Enlace público de venta + códigos de compra para compradores recurrentes.
    'pos-sales-link'            => ['method' => 'get',    'uri' => 'pos/sales-link',                         'action' => 'salesLink'],
    'pos-sales-link-regenerate' => ['method' => 'post',   'uri' => 'pos/sales-link/regenerate',              'action' => 'regenerateSalesLink'],
    'pos-purchase-codes'        => ['method' => 'get',    'uri' => 'pos/purchase-codes',                     'action' => 'purchaseCodes'],
    'pos-purchase-codes-issue'  => ['method' => 'post',   'uri' => 'pos/purchase-codes',                     'action' => 'issuePurchaseCode'],
    'pos-purchase-codes-revoke' => ['method' => 'delete', 'uri' => 'pos/purchase-codes/{purchaseCode}',      'action' => 'revokePurchaseCode'],
    'available-countries' => ['method' => 'get', 'uri' => '{room}/available-countries', 'action' => 'availableCountries'],
    'card-price'          => ['method' => 'get', 'uri' => '{room}/card-price',           'action' => 'cardPrice'],
]);

/**
 * ====================================
 * HISTORIAL DE PARTIDAS (JUGADOR)
 * ====================================
 */
Route::get('bingo/my-games', [PlayerGameHistoryController::class, 'index'])
    ->middleware(['auth', 'multiPermission:games.bingo,games.bingo.game_rooms'])
    ->name('bingo.my-games');

Route::get('bingo/my-games/{roomId}/detail', [PlayerGameHistoryController::class, 'detail'])
    ->middleware(['auth', 'multiPermission:games.bingo,games.bingo.game_rooms'])
    ->name('bingo.my-games.detail');

/**
 * ====================================
 * SALAS DE JUEGO
 * 'verified': buy/player-buy/join-by-code cobran dinero real — un email
 * sin verificar no debe poder mover fondos.
 * ====================================
 */
route_group('bingo/game-rooms', 'bingo-game-rooms.', ['auth', 'verified', 'multiPermission:games.bingo,games.bingo.game_rooms'], BingoRoomController::class, [
    'index' => ['method' => 'get',  'uri' => '',           'action' => 'viewGameRooms'],
    'join-by-code'     => ['method' => 'post', 'uri' => 'join-by-code', 'action' => 'joinByCode', 'middleware' => ['throttle:bingo-join-by-code']],
    'filter-data'      => ['method' => 'get',  'uri' => 'filter-data', 'action' => 'filterData'],
    // Sin ruta de compra para comercios/vendedores: solo generan cartones para
    // jugadores externos, desde el punto de venta (generate, con comprobante).
    // Antes existía '{room}/buy', que les generaba cartones a su propio nombre
    // sin cobro ni comprobante.
    'player-buy'       => ['method' => 'post', 'uri' => '{room}/player-buy', 'action' => 'playerBuyFromGameRooms', 'middleware' => ['throttle:bingo-player-buy']],
    'check-user-role'  => ['method' => 'get',  'uri' => 'check-user-role', 'action' => 'checkUserRole'],
    'player-card-price' => ['method' => 'get', 'uri' => '{room}/player-card-price', 'action' => 'playerCardPrice'],
    'top-buyers'       => ['method' => 'get', 'uri' => '{room}/top-buyers', 'action' => 'topBuyers'],
    'verify-access'    => ['method' => 'post', 'uri' => '{room}/verify-access', 'action' => 'verifyAccess', 'middleware' => ['throttle:bingo-verify-access']],
]);

/**
 * ====================================
 * JUEGO DE BINGO
 * ====================================
 */
// NOTA: 'game' (inGame), 'draw-state' y 'check-bingo' NO están cubiertos por
// authorizeResource() en el controlador -- resourceAbilityMap() solo mapea
// index/show/create/store/edit/update/destroy, no estas acciones -- así que
// el middleware multiPermission de abajo solo exige el permiso GENERAL del
// módulo, no pertenencia a la sala. Cada action llama explícitamente
// $this->authorize('viewGame', $room) (BingoRoomPolicy::viewGame), que exige
// que el jugador tenga cartones comprados en ESA sala específica.
// 'verified': check-bingo acredita el premio en dinero real — un email
// sin verificar no debe poder cobrar.
route_group('bingo', 'bingo.', ['auth', 'verified', 'multiPermission:games.bingo,games.bingo.game_rooms'], BingoGameController::class, [
    'lobby'         => ['method' => 'get',  'uri' => '{room}/lobby',         'action' => 'lobby'],
    'lobby-players' => ['method' => 'get',  'uri' => '{room}/lobby-players', 'action' => 'lobbyPlayers'],
    'game'          => ['method' => 'get',  'uri' => '{room}/game',          'action' => 'inGame'],
    'draw-state'    => ['method' => 'get',  'uri' => '{room}/draw-state',    'action' => 'drawState'],
    'user-cards'    => ['method' => 'get',  'uri' => '{room}/user-cards',    'action' => 'getUserCards'],
    'check-bingo'   => ['method' => 'post', 'uri' => '{room}/check-bingo',   'action' => 'checkBingo', 'middleware' => ['throttle:bingo-check-bingo']],
    'view-card'     => ['method' => 'get',  'uri' => '{room}/card/{card}',   'action' => 'viewCard'],
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
 * REPORTES Y ESTADÍSTICAS
 *
 * El grupo exige games.manage,games.bingo (acceso general) pero cada acción
 * suma su permiso GRANULAR específico vía 'middleware' por ruta — antes
 * faltaba: cualquier 'player' (tiene games.bingo, para JUGAR) podía entrar
 * por URL directa a /bingo/winners o /bingo/sales-report (datos de negocio:
 * comisiones, ventas por vendedor) aunque el sidebar ya se los ocultaba
 * correctamente exigiendo games.bingo.winners/sales_report — el hueco estaba
 * en la ruta, no en el menú. BingoReportController::authorizeResource()
 * (BingoRoom::class) solo cubre las 3 acciones con {room}
 * (sales-report-room*), el resto no tenía ningún chequeo granular.
 * ====================================
 */
route_group('bingo', 'bingo.', ['auth', 'multiPermission:games.manage,games.bingo'], BingoReportController::class, [
    'cards-sold'             => ['method' => 'get', 'uri' => 'cards-sold', 'action' => 'cardsSold', 'middleware' => ['multiPermission:games.manage,games.bingo.sales_report']],
    'cards-sold-data'        => ['method' => 'get', 'uri' => 'cards-sold-data', 'action' => 'cardsSoldData', 'middleware' => ['multiPermission:games.manage,games.bingo.sales_report']],
    'cards-sold-export'      => ['method' => 'get', 'uri' => 'cards-sold-export', 'action' => 'cardsSoldExport', 'middleware' => ['multiPermission:games.manage,games.bingo.sales_report']],
    'sales-report'           => ['method' => 'get', 'uri' => 'sales-report', 'action' => 'salesReport', 'middleware' => ['multiPermission:games.manage,games.bingo.sales_report']],
    'sales-report-data'      => ['method' => 'get', 'uri' => 'sales-report-data', 'action' => 'salesReportData', 'middleware' => ['multiPermission:games.manage,games.bingo.sales_report']],
    'sales-report-export'    => ['method' => 'get', 'uri' => 'sales-report-export', 'action' => 'salesReportExport', 'middleware' => ['multiPermission:games.manage,games.bingo.sales_report']],
    'sales-report-room'      => ['method' => 'get', 'uri' => 'sales-report/room/{room}', 'action' => 'salesReportByRoom', 'middleware' => ['multiPermission:games.manage,games.bingo.sales_report']],
    'sales-report-room-data' => ['method' => 'get', 'uri' => 'sales-report-room-data/{room}', 'action' => 'salesReportByRoomData', 'middleware' => ['multiPermission:games.manage,games.bingo.sales_report']],
    'sales-report-room-export' => ['method' => 'get', 'uri' => 'sales-report-room-export/{room}', 'action' => 'salesReportByRoomExport', 'middleware' => ['multiPermission:games.manage,games.bingo.sales_report']],
    'winners'                => ['method' => 'get', 'uri' => 'winners', 'action' => 'winners', 'middleware' => ['multiPermission:games.manage,games.bingo.winners']],
    'winners-data'           => ['method' => 'get', 'uri' => 'winners-data', 'action' => 'winnersData', 'middleware' => ['multiPermission:games.manage,games.bingo.winners']],
    'winners-export'         => ['method' => 'get', 'uri' => 'winners-export', 'action' => 'winnersExport', 'middleware' => ['multiPermission:games.manage,games.bingo.winners']],
]);

/**
 * ====================================
 * REVISIÓN DE PAGOS DE JUGADORES EXTERNOS
 * ====================================
 * Cola de aprobación/rechazo de pagos PENDING (ver
 * BingoCardPaymentReviewService — antes se marcaban COMPLETED sin revisión).
 *
 * games.bingo.payments.review (admin + commerce, ver migración
 * 2026_09_30_100000): mismo permiso que el ítem "Revisión de Pagos" del
 * sidebar. Antes games.bingo (lo tiene el seller: podía aprobar sus propias
 * ventas) y luego games.bingo.manage (agencia/cliente entraban a una cola
 * siempre vacía). El scope fino (commerce → solo su red; admin → todo) vive
 * en BingoCardPaymentReviewController.
 */
route_group('bingo/card-payments', 'bingo-card-payments.', ['auth', 'multiPermission:games.manage,games.bingo.payments.review'], \App\Http\Controllers\Games\Bingo\Payment\BingoCardPaymentReviewController::class, [
    'index'   => ['method' => 'get',  'uri' => '',                'action' => 'index'],
    'policy'  => ['method' => 'patch', 'uri' => 'policy',         'action' => 'updatePolicy'],
    'proof'   => ['method' => 'get',  'uri' => '{payment}/proof', 'action' => 'proof'],
    'approve' => ['method' => 'post', 'uri' => '{payment}/approve', 'action' => 'approve'],
    'reject'  => ['method' => 'post', 'uri' => '{payment}/reject',  'action' => 'reject'],
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

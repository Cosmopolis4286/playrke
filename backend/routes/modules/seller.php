<?php

use App\Http\Controllers\Games\Bingo\BingoController;
use App\Http\Controllers\Roles\Seller\ManagerSellerController;
use App\Http\Controllers\Roles\Seller\SellerProfileController;
use App\Http\Controllers\Roles\SellerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Vendedores/Agencias/Taquillas
|--------------------------------------------------------------------------
| Gestión de vendedores y sus operaciones de venta de cartones.
*/

// GESTIÓN DE VENDEDORES (MANAGER SELLER ROLE)
// ====================================
multi_middleware_routes('sellers', 'manager-seller.', [
    [
        // 'admin'/'superadmin' agregados: /admin/sellers (AdminController::sellers())
        // ya mostraba el listado con middleware role:admin,superadmin, pero el
        // CRUD real vive aquí bajo /sellers — sin 'admin' en este middleware,
        // un admin (no superadmin, que sí tiene bypass total vía
        // RoleAuthorizationService::isSuperadmin()) veía la tabla pero recibía
        // 403 al crear/editar/eliminar/resetear contraseña de un vendedor.
        'middleware' => ['auth', 'role:commerce,subsidiary,customer,admin,superadmin'],
        'controller' => ManagerSellerController::class,
        'routes' => [
            'index' => ['method' => 'get', 'uri' => ''],
            'store' => ['method' => 'post', 'uri' => ''],
            'fetchList' => ['method' => 'get', 'uri' => 'fetch-list'],
            'managerOptions' => ['method' => 'get', 'uri' => 'manager-options', 'action' => 'managerOptions'],
            'show' => ['method' => 'get', 'uri' => '{seller}/show'],
            'update' => ['method' => 'put', 'uri' => '{seller}/update'],
            'delete' => ['method' => 'put', 'uri' => '{seller}/delete'],
            'destroy' => ['method' => 'delete', 'uri' => '{seller}'],
            'toggleStatus' => ['method' => 'put', 'uri' => '{seller}/status'],
            'resetPassword' => ['method' => 'put', 'uri' => '{seller}/reset-password'],
        ]
    ]
]);

// ====================================
// OPERACIONES DE VENTA (SELLER ROLE)
// ====================================
multi_middleware_routes('seller', 'seller.', [
    [
        'middleware' => ['auth', 'role:seller'],
        'controller' => SellerController::class,
        'routes' => [
            'price-currency' => ['method' => 'get', 'uri' => 'bingo/price-currency/{room}/{country}', 'action' => 'getCardPriceCurrency'],
            'player-info' => ['method' => 'get', 'uri' => 'bingo/player/{playerLink}/info', 'action' => 'getPlayerInfo'],
            // player-cartons y sales-report-player: sin implementación en el controlador
            // (funcionalidad nunca desarrollada, no un bug de nomenclatura — quedan sin action).
            'player-cartons' => ['method' => 'get', 'uri' => 'bingo/player/cartons/{playerLink}/{room}'],
            // throttle:20,1 — antes sin límite, permitía a un seller iterar
            // teléfonos/rooms buscando jugadores de otros vendedores más
            // rápido que a mano (ver también el scope agregado en
            // SellerController::getPlayer()).
            'get-player' => ['method' => 'post', 'uri' => 'bingo/player/search', 'action' => 'getPlayer', 'middleware' => ['throttle:20,1']],
            'payment-details' => ['method' => 'get', 'uri' => 'payment-details/{currency}', 'action' => 'getPaymentCommerceDetails'],
            'sales-report-player' => ['method' => 'get', 'uri' => 'bingo/sales-report/{room}/player/{playerLink}'],
        ]
    ],
]);

// ====================================
// AUTOGESTIÓN DE FICHA PROPIA (SELLER)
// ====================================
// Distinto de sellers/* (manager-seller.*, gestión por Commerce/Subsidiary,
// multiPermission sellers.*) — aquí un usuario 'seller' opera EXCLUSIVAMENTE
// sobre su propio registro, resuelto server-side por SellerProfileService,
// nunca por un {id} de la URL.
// sellers.view agregado: permite la vista administrativa (target_user_id,
// ver AdminProfileAccessService) a admin/superadmin, que NUNCA tienen
// sellers.manage-own (ese permiso es exclusivo del propio vendedor). Sin
// esto, MultiPermissionMiddleware devolvía 302 a dashboard antes de que la
// request llegara al controlador — el bypass de superadmin tampoco alcanza
// a un usuario con solo el rol 'admin'.
Route::middleware(['auth', 'multiPermission:sellers.manage-own,sellers.view'])->group(function () {
    Route::get('seller/profile', [SellerProfileController::class, 'show'])->name('seller.profile.show');
    Route::put('seller/profile', [SellerProfileController::class, 'update'])->name('seller.profile.update');
});

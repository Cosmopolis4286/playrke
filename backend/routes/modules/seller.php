<?php

use App\Http\Controllers\Games\Bingo\BingoController;
use App\Http\Controllers\Roles\Seller\ManagerSellerController;
use App\Http\Controllers\Roles\SellerController;

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
        'middleware' => ['auth', 'role:admin,commerce,customer,subsidiary'],
        'controller' => ManagerSellerController::class,
        'routes' => [
            'index' => ['method' => 'get', 'uri' => ''],
            'store' => ['method' => 'post', 'uri' => ''],
            'fetchList' => ['method' => 'get', 'uri' => 'fetch-list'],
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
            'price-currency' => ['method' => 'get', 'uri' => 'bingo/price-currency/{room}/{country}'],
            'player-info' => ['method' => 'get', 'uri' => 'bingo/player/{playerLink}/info'],
            'player-cartons' => ['method' => 'get', 'uri' => 'bingo/player/cartons/{playerLink}/{room}'],
            'get-player' => ['method' => 'post', 'uri' => 'bingo/player/search'],
            'payment-details' => ['method' => 'get', 'uri' => 'payment-details/{currency}'],
            'sales-report-player' => ['method' => 'get', 'uri' => 'bingo/sales-report/{room}/player/{playerLink}'],
        ]
    ],
]);

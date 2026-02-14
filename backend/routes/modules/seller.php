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
        'middleware' => ['auth', 'role:commerce,customer'],
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
        'middleware' => ['auth', 'seller'],
        'controller' => SellerController::class,
        'routes' => [
            'payment-status' => ['method' => 'post', 'uri' => 'bingo/carton/payment/status'],
            'price-currency' => ['method' => 'get', 'uri' => 'bingo/price-currency/{room}/{country}'],
            'winner-info' => ['method' => 'get', 'uri' => 'bingo/winner-info/{ctnId}'],
            'player-info' => ['method' => 'get', 'uri' => 'bingo/player/{playerLink}/info'],
            'player-cartons' => ['method' => 'get', 'uri' => 'bingo/player/cartons/{playerLink}/{room}'],
            'get-player' => ['method' => 'post', 'uri' => 'bingo/player/search'],
            'carton-price' => ['method' => 'get', 'uri' => 'bingo/carton/price/{roomId}'],
            'payment-details' => ['method' => 'get', 'uri' => 'payment-details/{currency}'],
            'sales-report-player' => ['method' => 'get', 'uri' => 'bingo/sales-report/{room}/player/{playerLink}'],
        ]
    ],
    [
        'middleware' => ['auth', 'seller'],
        'controller' => BingoController::class,
        'routes' => [
            'generate-card' => ['method' => 'post', 'uri' => 'bingo/generate-card'],
        ]
    ]
]);

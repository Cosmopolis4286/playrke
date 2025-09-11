<?php

use App\Http\Controllers\{SellerController, BingoController};

require_once app_path('Helpers/RouteHelper.php');

/*
|--------------------------------------------------------------------------
| Rutas de Vendedores/Agencias/Taquillas
|--------------------------------------------------------------------------
| Gestión de vendedores y sus operaciones de venta de cartones.
*/

// ====================================
// GESTIÓN DE VENDEDORES (COMMERCE)
// ====================================
role_protected_routes(['commerce_id'], 'seller', SellerController::class, [
    'create' => ['method' => 'post', 'uri' => ''],
    'show' => ['method' => 'get', 'uri' => '{id}'],
    'update' => ['method' => 'put', 'uri' => '{id}'],
    'destroy' => ['method' => 'delete', 'uri' => '{id}'],
    'changePassword' => ['method' => 'put', 'uri' => '{id}/password'],
    'changeStatus' => ['method' => 'put', 'uri' => '{id}/status'],
]);

// ====================================
// OPERACIONES DE VENTA (SELLER ROLE)
// ====================================
multi_middleware_routes('seller', 'seller.', [
    [
        'middleware' => ['auth', 'seller'],
        'controller' => SellerController::class,
        'routes' => [
            'carton-generate' => ['method' => 'get', 'uri' => 'bingo/carton/generate'],
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
            'generate-carton' => ['method' => 'post', 'uri' => 'bingo/carton/generate'],
        ]
    ]
]);
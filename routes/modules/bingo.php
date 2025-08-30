<?php

use App\Http\Controllers\BingoController;
use Illuminate\Support\Facades\Route;

require_once app_path('Helpers/RouteHelper.php');

mapControllerRoutes('bingo', 'bingo.', [
    [
        'middleware' => ['auth', 'commerce', 'admin'],
        'controller' => BingoController::class,
        'routes' => [
            'create'        => ['method' => 'post',   'uri' => 'create'],
            'getInfo'       => ['method' => 'get',    'uri' => 'getInfo/{id}'],
            'update'        => ['method' => 'put',    'uri' => 'update/{id}'],
            'delete'        => ['method' => 'delete', 'uri' => 'delete/{id}'],
            'changePassw'   => ['method' => 'put',    'uri' => 'changePassw/{id}'],
            'changeStatus'  => ['method' => 'put',    'uri' => 'changeStatus/{id}'],
        ],
    ],
    [
        'middleware' => ['auth', 'commerce'],
        'controller' => BingoController::class,
        'routes' => [
            'rewards'                => ['method' => 'get', 'uri' => 'rewards'],
            'sellers'                => ['method' => 'get', 'uri' => 'sellers'],
            'getCoinByCountry'       => ['method' => 'get', 'uri' => 'seller/getCoinByCountry/{country}/{seller}'],
            'getSellerInfoByCtn'     => ['method' => 'get', 'uri' => 'seller/getSellerInfoByCtn/{ctnId}'],
            'ctnsLotPayment'         => ['method' => 'put', 'uri' => 'bingo/carton-lot/payment'],
        ],
    ]
]);

// Ruta pública de pago (sin middleware)
Route::get(
    'commerce/getPaymentCommerceDetails/{commerce}/{coin}',
    [BingoController::class, 'getPaymentCommerceDetails']
)
    ->withoutMiddleware(['auth', 'commerce'])
    ->name('commerce.getPaymentCommerceDetails');

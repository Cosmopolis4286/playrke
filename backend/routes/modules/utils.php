<?php

// use App\Http\Controllers\CookieController;

use App\Http\Controllers\Games\GameController;
use App\Http\Controllers\Prizes\PrizeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Utilidades Generales
|--------------------------------------------------------------------------
| Funciones auxiliares: cookies, juegos generales, recompensas.
*/

// ====================================
// MANEJO DE COOKIES
// ====================================
// Route::middleware('auth')
//     ->get('cookie/referral', [CookieController::class, 'getReferral'])
//     ->name('cookie.referral');

// ====================================
// JUEGOS GENERALES
// ====================================
Route::put('games/handle-customer/{id?}', [GameController::class, 'handleCustomerGame'])
    ->name('games.handle-customer');

// ====================================
// SISTEMA DE RECOMPENSAS
// ====================================
route_group('prizes', 'prizes.', ['auth'], PrizeController::class, [
    'index' => ['method' => 'get', 'uri' => ''],
    'country' => ['method' => 'get', 'uri' => '{countryCode}'],
]);

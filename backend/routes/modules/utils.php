<?php

use App\Http\Controllers\{GameController, RewardsController};
// use App\Http\Controllers\CookieController;
use Illuminate\Support\Facades\Route;

require_once app_path('Helpers/RouteHelper.php');

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
crud_routes('rewards', RewardsController::class, ['auth', 'commerce_id']);
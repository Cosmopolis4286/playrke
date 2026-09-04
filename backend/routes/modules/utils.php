<?php

// use App\Http\Controllers\CookieController;

use App\Http\Controllers\Games\GameController;
use App\Http\Controllers\Prizes\PrizeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SidebarFavoriteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Utilidades Generales
|--------------------------------------------------------------------------
| Funciones auxiliares: cookies, juegos generales, recompensas.
*/

// ====================================
// BÚSQUEDA GLOBAL (navbar)
// ====================================
Route::get('/search', [SearchController::class, 'search'])
    ->middleware(['auth', 'web', 'throttle:30,1'])
    ->name('search');

// ====================================
// FAVORITOS DEL SIDEBAR
// ====================================
// {itemKey} incluye '.' (formato 'parent.child') — regex explícito porque
// el patrón por defecto de Laravel excluye '/' pero no requiere permitir
// '.', y RouteServiceProvider no declara un pattern global para esto.
Route::middleware(['auth', 'web'])->group(function () {
    Route::post('/sidebar/favorites', [SidebarFavoriteController::class, 'store'])
        ->name('sidebar.favorites.store');
    Route::delete('/sidebar/favorites/{itemKey}', [SidebarFavoriteController::class, 'destroy'])
        ->where('itemKey', '[A-Za-z0-9_.-]+')
        ->name('sidebar.favorites.destroy');
});

// ====================================
// MANEJO DE COOKIES
// ====================================
// Route::middleware('auth')
//     ->get('cookie/referral', [CookieController::class, 'getReferral'])
//     ->name('cookie.referral');

// ====================================
// JUEGOS GENERALES
// ====================================
// Route::put('games/handle-customer/{id?}', [GameController::class, 'handleCustomerGame'])
//     ->name('games.handle-customer');

// ====================================
// SISTEMA DE RECOMPENSAS
// ====================================
route_group('prizes', 'prizes.', ['auth'], PrizeController::class, [
    'index'        => ['method' => 'get',    'uri' => ''],
    'fetchList'    => ['method' => 'get',    'uri' => 'fetch-list'],
    'search'       => ['method' => 'get',    'uri' => 'search'],
    'store'        => ['method' => 'post',   'uri' => ''],
    'show'         => ['method' => 'get',    'uri' => '{prize}/show'],
    'update'       => ['method' => 'put',    'uri' => '{prize}/update'],
    'delete'       => ['method' => 'put',    'uri' => '{prize}/delete'],
    'destroy'      => ['method' => 'delete', 'uri' => '{prize}'],
    'toggleStatus' => ['method' => 'put',    'uri' => '{prize}/status'],
]);

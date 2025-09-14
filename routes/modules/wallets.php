<?php

use App\Http\Controllers\{WalletController, BinanceController};
use Illuminate\Support\Facades\Route;

require_once app_path('Helpers/RouteHelper.php');

/*
|--------------------------------------------------------------------------
| Rutas de Billeteras y Criptomonedas
|--------------------------------------------------------------------------
| Gestión de billeteras digitales, criptomonedas y intercambios.
*/

// ====================================
// BILLETERA PRINCIPAL
// ====================================
route_group('wallet', 'wallet.', ['auth'], WalletController::class, [
    'index' => ['method' => 'get', 'uri' => ''],
]);

// ====================================
// BINANCE (CRIPTOMONEDAS)
// ====================================
crud_routes('binance', BinanceController::class, ['auth'], ['index', 'destroy']);
Route::middleware('auth')->group(function () {
    Route::delete('binance/{id}', [BinanceController::class, 'destroy'])->name('binance.destroy');
    Route::get('binance/{id}/info', [BinanceController::class, 'show'])->name('binance.show');
});
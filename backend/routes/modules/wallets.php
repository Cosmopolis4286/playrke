<?php

use App\Http\Controllers\Directories\Crypto\BinanceController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Billeteras y Criptomonedas
|--------------------------------------------------------------------------
| Gestión de billeteras digitales, criptomonedas y intercambios.
*/

// ====================================
// BILLETERA DEL JUGADOR
// ====================================
Route::middleware(['auth', 'role:player'])->group(function () {
    Route::get('wallet',                              [WalletController::class, 'index'])     ->name('wallet.index');
    Route::post('wallet/deposit',                     [WalletController::class, 'deposit'])   ->name('wallet.deposit');
    Route::post('wallet/withdraw',                    [WalletController::class, 'withdraw'])  ->name('wallet.withdraw');
    Route::get('wallet/{walletId}/movements',         [WalletController::class, 'movements']) ->name('wallet.movements');
});

// ====================================
// BINANCE (CRIPTOMONEDAS)
// ====================================
crud_routes('binance', BinanceController::class, ['auth'], ['index', 'destroy']);
Route::middleware('auth')->group(function () {
    Route::delete('binance/{id}', [BinanceController::class, 'destroy'])->name('binance.destroy');
    Route::get('binance/{id}/info', [BinanceController::class, 'show'])->name('binance.show');
});

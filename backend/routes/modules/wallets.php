<?php

use App\Http\Controllers\Directories\Crypto\BinanceController;
use App\Http\Controllers\Wallet\WalletMovementController;
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
Route::middleware(['auth', 'multiPermission:wallet.view,wallet.deposit,wallet.withdraw,wallet.movements'])->group(function () {
    Route::get('wallet',                              [WalletController::class, 'index'])     ->name('wallet.index');
    Route::post('wallet/deposit',                     [WalletController::class, 'deposit'])   ->name('wallet.deposit');
    Route::post('wallet/withdraw',                    [WalletController::class, 'withdraw'])  ->name('wallet.withdraw');
    Route::get('wallet/{walletId}/movements',         [WalletController::class, 'movements']) ->name('wallet.movements');
});

// ====================================
// GESTIÓN DE MOVIMIENTOS (ADMIN/SUPERADMIN)
// ====================================
Route::middleware(['auth', 'multiPermission:wallet.movements.view,wallet.movements.manage'])->group(function () {
    Route::get('wallet-movements',              [WalletMovementController::class, 'index'])     ->name('wallet-movements.index');
    Route::get('wallet-movements/fetch',        [WalletMovementController::class, 'fetchList']) ->name('wallet-movements.fetch');
    Route::get('wallet-movements/{movement}',   [WalletMovementController::class, 'show'])     ->name('wallet-movements.show');
});

// ====================================
// BINANCE (CRIPTOMONEDAS)
// ====================================
crud_routes('binance', BinanceController::class, ['auth'], ['index', 'destroy']);
Route::middleware('auth')->group(function () {
    Route::delete('binance/{id}', [BinanceController::class, 'destroy'])->name('binance.destroy');
    Route::get('binance/{id}/info', [BinanceController::class, 'show'])->name('binance.show');
});

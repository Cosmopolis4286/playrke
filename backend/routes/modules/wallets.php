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
    Route::get('wallet',                        [WalletController::class, 'index'])            ->name('wallet.index');
    Route::get('wallet/movements',              [WalletController::class, 'movements'])        ->name('wallet.movements');
    Route::get('wallet/directories',            [WalletController::class, 'userDirectories'])  ->name('wallet.directories');
    Route::get('wallet/pending-operation',      [WalletController::class, 'pendingOperation']) ->name('wallet.pending-operation');
    Route::get('wallet/limits',                 [WalletController::class, 'limits'])           ->name('wallet.limits');

    // Operaciones financieras: requieren TOS + throttle antifraude
    // throttle:5,1 → máximo 5 intentos por minuto por usuario
    Route::middleware(['tos', 'throttle:5,1'])->group(function () {
        Route::post('wallet/deposit',  [WalletController::class, 'deposit'])  ->name('wallet.deposit');
        Route::post('wallet/withdraw', [WalletController::class, 'withdraw']) ->name('wallet.withdraw');
    });
});

// ====================================
// COMPROBANTES (autenticado — evita exposición pública)
// ====================================
Route::middleware('auth')->get('wallet/receipt/{filename}', function (string $filename) {
    // Solo letras, números, guiones y extensión — sin path traversal
    if (! preg_match('/^[\w\-]+\.(jpg|jpeg|png|pdf|webp)$/i', $filename)) {
        abort(404);
    }
    $path = storage_path('app/public/wallet/receipts/' . $filename);
    if (! file_exists($path)) {
        abort(404);
    }
    $mime = mime_content_type($path) ?: 'application/octet-stream';
    return response()->file($path, ['Content-Type' => $mime]);
})->name('wallet.receipt');

// ====================================
// GESTIÓN DE MOVIMIENTOS (ADMIN/SUPERADMIN)
// ====================================
Route::middleware(['auth', 'multiPermission:wallet.movements.view,wallet.movements.manage'])->group(function () {
    Route::get('wallet-movements',                          [WalletMovementController::class, 'index'])     ->name('wallet-movements.index');
    Route::get('wallet-movements/fetch',                    [WalletMovementController::class, 'fetchList']) ->name('wallet-movements.fetch');
    Route::get('wallet-movements/{movement}',               [WalletMovementController::class, 'show'])      ->name('wallet-movements.show');
    Route::post('wallet-movements/{movement}/approve',      [WalletMovementController::class, 'approve'])   ->name('wallet-movements.approve');
    Route::post('wallet-movements/{movement}/reject',       [WalletMovementController::class, 'reject'])    ->name('wallet-movements.reject');
});

// ====================================
// BINANCE (CRIPTOMONEDAS)
// ====================================
crud_routes('binance', BinanceController::class, ['auth'], ['index', 'destroy']);
Route::middleware('auth')->group(function () {
    Route::delete('binance/{id}', [BinanceController::class, 'destroy'])->name('binance.destroy');
    Route::get('binance/{id}/info', [BinanceController::class, 'show'])->name('binance.show');
});

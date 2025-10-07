<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BingoController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\Admin\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes - PlayRKE Bingo API
|--------------------------------------------------------------------------
| API RESTful para frontend Nuxt 3
| Autenticación: Laravel Sanctum (stateful cookies)
*/

// ====================================
// AUTHENTICATION ROUTES (Public)
// ====================================
Route::prefix('api/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('api.auth.forgot-password');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('api.auth.reset-password');
});

// ====================================
// AUTHENTICATED ROUTES
// ====================================
Route::middleware('auth:sanctum')->prefix('api')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('user', [AuthController::class, 'user'])->name('api.auth.user');
    });

    // User Profile
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'profile'])->name('api.user.profile');
        Route::put('profile', [UserController::class, 'updateProfile'])->name('api.user.update-profile');
        Route::put('password', [UserController::class, 'updatePassword'])->name('api.user.update-password');
        Route::post('avatar', [UserController::class, 'uploadAvatar'])->name('api.user.upload-avatar');
        Route::delete('avatar', [UserController::class, 'deleteAvatar'])->name('api.user.delete-avatar');
        Route::get('preferences', [UserController::class, 'getPreferences'])->name('api.user.preferences');
        Route::put('preferences', [UserController::class, 'updatePreferences'])->name('api.user.update-preferences');
        Route::get('stats', [UserController::class, 'getStats'])->name('api.user.stats');
    });

    // Bingo Games
    Route::prefix('bingo')->group(function () {
        // Rooms
        Route::get('rooms', [BingoController::class, 'getRooms'])->name('api.bingo.rooms');
        Route::get('rooms/{id}', [BingoController::class, 'getRoom'])->name('api.bingo.room');

        // Cards
        Route::post('rooms/{id}/purchase', [BingoController::class, 'purchaseCards'])->name('api.bingo.purchase');
        Route::get('rooms/{id}/cards', [BingoController::class, 'getPlayerCards'])->name('api.bingo.player-cards');

        // Game
        Route::get('rooms/{id}/draw', [BingoController::class, 'getDraw'])->name('api.bingo.draw');
        Route::get('draw/{id}/numbers', [BingoController::class, 'getCalledNumbers'])->name('api.bingo.called-numbers');
        Route::post('cards/{id}/check-winner', [BingoController::class, 'checkWinner'])->name('api.bingo.check-winner');
        Route::get('rooms/{id}/winners', [BingoController::class, 'getWinners'])->name('api.bingo.winners');

        // History
        Route::get('history', [BingoController::class, 'getPlayerHistory'])->name('api.bingo.history');
        Route::post('history/{id}/notify-payment', [BingoController::class, 'notifyPrizePayment'])->name('api.bingo.notify-payment');
    });

    // Wallet
    Route::prefix('wallet')->group(function () {
        Route::get('/', [WalletController::class, 'getWallets'])->name('api.wallet.list');
        Route::get('balance', [WalletController::class, 'getBalance'])->name('api.wallet.balance');
        Route::post('deposit', [WalletController::class, 'deposit'])->name('api.wallet.deposit');
        Route::post('withdraw', [WalletController::class, 'withdraw'])->name('api.wallet.withdraw');
        Route::get('transactions', [WalletController::class, 'getTransactions'])->name('api.wallet.transactions');
        Route::get('transactions/{id}', [WalletController::class, 'getTransaction'])->name('api.wallet.transaction');
    });

    // Admin Routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Dashboard
        Route::get('dashboard', [AdminController::class, 'dashboard'])->name('api.admin.dashboard');

        // Customers
        Route::get('customers', [AdminController::class, 'getCustomers'])->name('api.admin.customers');
        Route::get('customers/{id}', [AdminController::class, 'getCustomer'])->name('api.admin.customer');
        Route::post('customers', [AdminController::class, 'createCustomer'])->name('api.admin.create-customer');
        Route::put('customers/{id}', [AdminController::class, 'updateCustomer'])->name('api.admin.update-customer');
        Route::delete('customers/{id}', [AdminController::class, 'deleteCustomer'])->name('api.admin.delete-customer');

        // Treasurers
        Route::get('treasurers', [AdminController::class, 'getTreasurers'])->name('api.admin.treasurers');
        Route::get('treasurers/{id}', [AdminController::class, 'getTreasurer'])->name('api.admin.treasurer');
        Route::post('treasurers', [AdminController::class, 'createTreasurer'])->name('api.admin.create-treasurer');
        Route::put('treasurers/{id}', [AdminController::class, 'updateTreasurer'])->name('api.admin.update-treasurer');
        Route::delete('treasurers/{id}', [AdminController::class, 'deleteTreasurer'])->name('api.admin.delete-treasurer');

        // Sellers
        Route::get('sellers', [AdminController::class, 'getSellers'])->name('api.admin.sellers');
        Route::get('sellers/{id}', [AdminController::class, 'getSeller'])->name('api.admin.seller');
        Route::post('sellers', [AdminController::class, 'createSeller'])->name('api.admin.create-seller');
        Route::put('sellers/{id}', [AdminController::class, 'updateSeller'])->name('api.admin.update-seller');
        Route::delete('sellers/{id}', [AdminController::class, 'deleteSeller'])->name('api.admin.delete-seller');

        // Rooms Management
        Route::get('rooms', [AdminController::class, 'getRooms'])->name('api.admin.rooms');
        Route::get('rooms/{id}', [AdminController::class, 'getRoom'])->name('api.admin.room');
        Route::post('rooms', [AdminController::class, 'createRoom'])->name('api.admin.create-room');
        Route::put('rooms/{id}', [AdminController::class, 'updateRoom'])->name('api.admin.update-room');
        Route::delete('rooms/{id}', [AdminController::class, 'deleteRoom'])->name('api.admin.delete-room');
        Route::post('rooms/{id}/start', [AdminController::class, 'startGame'])->name('api.admin.start-game');

        // Transactions
        Route::get('transactions', [AdminController::class, 'getTransactions'])->name('api.admin.transactions');
        Route::get('transactions/{id}', [AdminController::class, 'getTransaction'])->name('api.admin.transaction');
        Route::post('transactions/{id}/approve', [AdminController::class, 'approveTransaction'])->name('api.admin.approve-transaction');
        Route::post('transactions/{id}/reject', [AdminController::class, 'rejectTransaction'])->name('api.admin.reject-transaction');

        // Reports
        Route::get('reports', [AdminController::class, 'getReports'])->name('api.admin.reports');
        Route::get('reports/export', [AdminController::class, 'exportReport'])->name('api.admin.export-report');

        // Settings
        Route::get('settings', [AdminController::class, 'getSettings'])->name('api.admin.settings');
        Route::put('settings', [AdminController::class, 'updateSettings'])->name('api.admin.update-settings');
    });
});

// ====================================
// HEALTH CHECK (Public)
// ====================================
Route::get('api/ping', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0'),
        'service' => 'PlayRKE Bingo API'
    ]);
})->name('api.ping');

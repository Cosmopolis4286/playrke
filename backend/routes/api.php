<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BingoController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Auth\DemoCredentialsController;

/*
|--------------------------------------------------------------------------
| API Routes - PlayRKE Bingo API
|--------------------------------------------------------------------------
| API REST para el frontend Nuxt
| Autenticación basada en cookies de sesión con Sanctum.
|
| IMPORTANTE para SPA:
| - Antes de cualquier login, el frontend debe llamar:
|   GET /sanctum/csrf-cookie
|
| - Las rutas de login, logout, user y cualquier
|   área que requiera sesión DEBEN tener middleware web.
|
*/

//
// PUBLIC ROUTES
//

// Información del usuario actual (si está autenticado)
// Route::middleware('web')->get('user', [AuthController::class, 'user'])->name('api.user');

// Auth (Login / Register / Reset)
// Route::middleware('web')->prefix('auth')->group(function () {
//     Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
//     Route::post('register', [AuthController::class, 'register'])->name('api.auth.register');
//     Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('api.auth.forgot-password');
//     Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('api.auth.reset-password');
// });

// Demo credentials (solo en local)
// Route::middleware('web')->get('demo-credentials', [DemoCredentialsController::class, 'index'])
//     ->name('api.demo-credentials');

//
// PROTECTED ROUTES (auth:sanctum)
//
// Route::middleware(['web', 'auth:sanctum'])->group(function () {
//     //
//     // AUTH
//     //
//     Route::prefix('auth')->group(function () {
//         Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
//         Route::get('user', [AuthController::class, 'user'])->name('api.auth.user');
//         Route::get('me', [AuthController::class, 'me'])->name('api.auth.me');
//     });

//     //
//     // USER PROFILE
//     //
//     Route::prefix('user')->group(function () {
//         Route::get('profile', [UserController::class, 'profile'])->name('api.user.profile');
//         Route::put('profile', [UserController::class, 'updateProfile'])->name('api.user.update-profile');
//         Route::put('password', [UserController::class, 'updatePassword'])->name('api.user.update-password');
//         Route::post('avatar', [UserController::class, 'uploadAvatar'])->name('api.user.upload-avatar');
//         Route::delete('avatar', [UserController::class, 'deleteAvatar'])->name('api.user.delete-avatar');
//         Route::get('preferences', [UserController::class, 'getPreferences'])->name('api.user.preferences');
//         Route::put('preferences', [UserController::class, 'updatePreferences'])->name('api.user.update-preferences');
//         Route::get('stats', [UserController::class, 'getStats'])->name('api.user.stats');
//     });

//     //
//     // BINGO
//     //
//     Route::prefix('bingo')->group(function () {

//         // Rooms
//         Route::get('rooms', [BingoController::class, 'getRooms'])->name('api.bingo.rooms');
//         Route::get('rooms/{id}', [BingoController::class, 'getRoom'])->name('api.bingo.room');

//         // Cards
//         Route::post('rooms/{id}/purchase', [BingoController::class, 'purchaseCards'])->name('api.bingo.purchase');
//         Route::get('rooms/{id}/cards', [BingoController::class, 'getPlayerCards'])->name('api.bingo.player-cards');

//         // Game
//         Route::get('rooms/{id}/draw', [BingoController::class, 'getDraw'])->name('api.bingo.draw');
//         Route::get('draw/{id}/numbers', [BingoController::class, 'getCalledNumbers'])->name('api.bingo.called-numbers');
//         Route::post('cards/{id}/check-winner', [BingoController::class, 'checkWinner'])->name('api.bingo.check-winner');
//         Route::get('rooms/{id}/winners', [BingoController::class, 'getWinners'])->name('api.bingo.winners');

//         // History
//         Route::get('history', [BingoController::class, 'getPlayerHistory'])->name('api.bingo.history');
//         Route::post('history/{id}/notify-payment', [BingoController::class, 'notifyPrizePayment'])->name('api.bingo.notify-payment');
//     });

//     //
//     // WALLET
//     //
//     Route::prefix('wallet')->group(function () {
//         Route::get('/', [WalletController::class, 'getWallets'])->name('api.wallet.list');
//         Route::get('balance', [WalletController::class, 'getBalance'])->name('api.wallet.balance');
//         Route::post('deposit', [WalletController::class, 'deposit'])->name('api.wallet.deposit');
//         Route::post('withdraw', [WalletController::class, 'withdraw'])->name('api.wallet.withdraw');
//         Route::get('transactions', [WalletController::class, 'getTransactions'])->name('api.wallet.transactions');
//         Route::get('transactions/{id}', [WalletController::class, 'getTransaction'])->name('api.wallet.transaction');
//     });

//     //
//     // ADMIN ROUTES
//     //
//     Route::middleware('role:admin')->prefix('admin')->group(function () {

//         // Dashboard
//         Route::get('dashboard', [AdminController::class, 'dashboard'])->name('api.admin.dashboard');

//         // Customers
//         Route::get('customers', [AdminController::class, 'getCustomers'])->name('api.admin.customers');
//         Route::get('customers/{id}', [AdminController::class, 'getCustomer'])->name('api.admin.customer');
//         Route::post('customers', [AdminController::class, 'createCustomer'])->name('api.admin.create-customer');
//         Route::put('customers/{id}', [AdminController::class, 'updateCustomer'])->name('api.admin.update-customer');
//         Route::delete('customers/{id}', [AdminController::class, 'deleteCustomer'])->name('api.admin.delete-customer');

//         // Treasurers
//         Route::get('treasurers', [AdminController::class, 'getTreasurers'])->name('api.admin.treasurers');
//         Route::get('treasurers/{id}', [AdminController::class, 'getTreasurer'])->name('api.admin.treasurer');
//         Route::post('treasurers', [AdminController::class, 'createTreasurer'])->name('api.admin.create-treasurer');
//         Route::put('treasurers/{id}', [AdminController::class, 'updateTreasurer'])->name('api.admin.update-treasurer');
//         Route::delete('treasurers/{id}', [AdminController::class, 'deleteTreasurer'])->name('api.admin.delete-treasurer');

//         // Sellers
//         Route::get('sellers', [AdminController::class, 'getSellers'])->name('api.admin.sellers');
//         Route::get('sellers/{id}', [AdminController::class, 'getSeller'])->name('api.admin.seller');
//         Route::post('sellers', [AdminController::class, 'createSeller'])->name('api.admin.create-seller');
//         Route::put('sellers/{id}', [AdminController::class, 'updateSeller'])->name('api.admin.update-seller');
//         Route::delete('sellers/{id}', [AdminController::class, 'deleteSeller'])->name('api.admin.delete-seller');

//         // Rooms
//         Route::get('rooms', [AdminController::class, 'getRooms'])->name('api.admin.rooms');
//         Route::get('rooms/{id}', [AdminController::class, 'getRoom'])->name('api.admin.room');
//         Route::post('rooms', [AdminController::class, 'createRoom'])->name('api.admin.create-room');
//         Route::put('rooms/{id}', [AdminController::class, 'updateRoom'])->name('api.admin.update-room');
//         Route::delete('rooms/{id}', [AdminController::class, 'deleteRoom'])->name('api.admin.delete-room');
//         Route::post('rooms/{id}/start', [AdminController::class, 'startGame'])->name('api.admin.start-game');

//         // Transactions
//         Route::get('transactions', [AdminController::class, 'getTransactions'])->name('api.admin.transactions');
//         Route::get('transactions/{id}', [AdminController::class, 'getTransaction'])->name('api.admin.transaction');
//         Route::post('transactions/{id}/approve', [AdminController::class, 'approveTransaction'])->name('api.admin.approve-transaction');
//         Route::post('transactions/{id}/reject', [AdminController::class, 'rejectTransaction'])->name('api.admin.reject-transaction');

//         // Reports
//         Route::get('reports', [AdminController::class, 'getReports'])->name('api.admin.reports');
//         Route::get('reports/export', [AdminController::class, 'exportReport'])->name('api.admin.export-report');

//         // Settings
//         Route::get('settings', [AdminController::class, 'getSettings'])->name('api.admin.settings');
//         Route::put('settings', [AdminController::class, 'updateSettings'])->name('api.admin.update-settings');
//     });
// });

// require __DIR__ . '/modules/content.php';         // TODO: Crear InformationController


//
// HEALTH CHECK
//
// Route::get('ping', function () {
//     return response()->json([
//         'status' => 'ok',
//         'timestamp' => now()->toISOString(),
//         'version' => config('app.version', '1.0'),
//         'service' => 'PlayRKE Bingo API'
//     ]);
// })->name('api.ping');

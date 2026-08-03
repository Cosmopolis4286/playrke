<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Roles\SuperAdminController;

/*
|--------------------------------------------------------------------------
| Rutas de SuperAdministración del Sistema
|--------------------------------------------------------------------------
| Gestión exclusiva para SuperAdmin con métricas extendidas.
| Todas protegidas por auth + role:superadmin.
*/

Route::middleware(['auth', 'role:superadmin'])->group(function () {

    /**
     * =========================================================
     * PANEL SUPERADMIN
     * =========================================================
     */
    Route::prefix('superadmin')
        ->as('superadmin.')
        ->controller(SuperAdminController::class)
        ->group(function () {
            Route::get('rke-summary', 'rkeSummary')->name('rke-summary');
            Route::get('platform-wallets', 'platformWallets')->name('platform-wallets');
            Route::get('platform-movements', 'platformMovements')->name('platform-movements');

            Route::middleware('permission:platform.wallet.manage')->group(function () {
                Route::post('platform-deposit', 'platformDeposit')->name('platform-deposit');
                Route::post('platform-withdraw', 'platformWithdraw')->name('platform-withdraw');
            });
        });

});

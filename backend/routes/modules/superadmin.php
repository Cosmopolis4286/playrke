<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Roles\SuperAdminController;
use App\Http\Controllers\Roles\SuperAdmin\FailedJobController;

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

    /**
     * =========================================================
     * JOBS FALLIDOS (widget "Salud del sistema" del dashboard)
     * =========================================================
     */
    Route::prefix('superadmin/failed-jobs')
        ->as('superadmin.failed-jobs.')
        ->controller(FailedJobController::class)
        ->group(function () {
            Route::get('', 'index')->name('index');
            Route::post('retry-all', 'retryAll')->name('retry-all');
            Route::post('{uuid}/retry', 'retry')->name('retry');
            Route::delete('{uuid}', 'forget')->name('forget');
        });

});

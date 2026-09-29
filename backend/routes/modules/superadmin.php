<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Roles\SuperAdminController;
use App\Http\Controllers\Roles\SuperAdmin\FailedJobController;
use App\Http\Controllers\Roles\SuperAdmin\ReconciliationController;

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
            Route::get('player-activity-summary', 'playerActivitySummary')->name('player-activity-summary');
            Route::get('seller-ranking', 'sellerRanking')->name('seller-ranking');
            Route::get('rke-summary', 'rkeSummary')->name('rke-summary');
            Route::get('commerce-hierarchy', 'commerceHierarchy')->name('commerce-hierarchy');
            Route::get('trivia-summary', 'triviaSummary')->name('trivia-summary');
            Route::get('exchange-rate-summary', 'exchangeRateSummary')->name('exchange-rate-summary');
            Route::get('platform-wallets', 'platformWallets')->name('platform-wallets');
            Route::get('platform-movements', 'platformMovements')->name('platform-movements');
            Route::get('system-health', 'systemHealth')->name('system-health');

            Route::middleware('permission:platform.wallet.manage')->group(function () {
                Route::post('platform-deposit', 'platformDeposit')->name('platform-deposit');
                Route::post('platform-withdraw', 'platformWithdraw')->name('platform-withdraw');
            });
        });

    /**
     * =========================================================
     * CONCILIACIÓN (cuentas por cobrar RKE + alertas de dinero)
     * =========================================================
     */
    Route::prefix('superadmin/reconciliation')
        ->as('superadmin.reconciliation.')
        ->controller(ReconciliationController::class)
        ->group(function () {
            Route::get('', 'index')->name('index');

            Route::middleware('permission:platform.wallet.manage')->group(function () {
                Route::post('receivables/settle', 'settleReceivables')->name('receivables.settle');
                Route::post('alerts/{id}/resolve', 'resolveAlert')->name('alerts.resolve');
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

/**
 * Reporte consolidado de flujo de caja cross-juego (Bingo + Trivia). Fuera del
 * grupo role:superadmin porque treasurer también tiene reports.financial/
 * treasury.reports y es precisamente el rol financiero al que este reporte
 * está dirigido — antes solo superadmin podía verlo pese a tener el permiso.
 * multiPermission siempre deja pasar a superadmin (ver
 * RoleAuthorizationService::hasAnyPermission()), así que no pierde acceso.
 */
Route::middleware(['auth', 'multiPermission:reports.financial,treasury.reports'])
    ->prefix('superadmin')
    ->as('superadmin.')
    ->controller(SuperAdminController::class)
    ->group(function () {
        Route::get('economy-summary', 'economySummary')->name('economy-summary');
    });

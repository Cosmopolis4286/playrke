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
            Route::get('', 'dashboard')->name('dashboard');
        });

});

<?php

use App\Http\Controllers\Commissions\CommissionLedgerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mis comisiones
|--------------------------------------------------------------------------
| Histórico de comisiones propias (vendedor, agencia, comercializadora,
| creador de salas). El middleware exige el permiso; el gate
| commissions.viewOwn (CommissionLedgerPolicy) además exige un rol de negocio.
*/
Route::middleware(['auth', 'multiPermission:commissions.view-own'])
    ->prefix('commissions')
    ->name('commissions.')
    ->controller(CommissionLedgerController::class)
    ->group(function() {
        Route::get('', 'index')->name('index');
        Route::get('data', 'data')->name('data');
        Route::get('export', 'export')->name('export');
    });

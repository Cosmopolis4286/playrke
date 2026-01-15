<?php

use App\Http\Controllers\Users\Roles\Treasurer\OtherTreasurersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Tesorería (Otros Tesoreros)
|--------------------------------------------------------------------------
| Funcionalidades específicas para tesoreros (otros tesoreros) y manejo de pagos.
*/

// ====================================
// DETALLES DE PARA OTROS TESOREROS
// ====================================
Route::group(['middleware' => ['auth', 'admin'], 'prefix' => 'other-treasurers', 'as' => 'other-treasurers.'], function () {
    Route::get('getInfo/{id}', [OtherTreasurersController::class, 'getInfo'])->name('getInfo');
    Route::put('update/{id}', [OtherTreasurersController::class, 'update'])->name('update');
    Route::delete('delete/{id}', [OtherTreasurersController::class, 'delete'])->name('delete');
    Route::put('changeStatus/{id}', [OtherTreasurersController::class, 'changeStatus'])->name('changeStatus');
});

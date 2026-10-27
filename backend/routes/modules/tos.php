<?php

use App\Http\Controllers\TosController;
use Illuminate\Support\Facades\Route;

Route::prefix('tos')->group(function() {
    // Cualquier usuario autenticado puede consultar la versión activa
    Route::get('current', [TosController::class, 'current'])->middleware('auth')->name('tos.current');

    // Solo usuarios autenticados pueden aceptar
    Route::post('accept', [TosController::class, 'accept'])->middleware('auth')->name('tos.accept');
});

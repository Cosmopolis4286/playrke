<?php

// use App\Http\Controllers\CookieController;
use App\Http\Controllers\Shared\{HomeController, InformationController};
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('dashboard', [HomeController::class, 'index'])->middleware('verified')->name('dashboard');

    // Obtener cookie referral
    // Route::get('cookie', [CookieController::class, 'getReferral'])->name('cookie.get');

    // Obtención hora servidor con zona horaria opcional
    Route::get('get/RKETIMESERV/{timeZone?}', [InformationController::class, 'getTimeServer'])->name('info.getTimeServer')->middleware('auth');
});

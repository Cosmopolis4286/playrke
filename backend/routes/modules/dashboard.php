<?php

// use App\Http\Controllers\CookieController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->middleware('verified')->name('dashboard');

    // Obtener cookie referral
    // Route::get('cookie', [CookieController::class, 'getReferral'])->name('cookie.get');
});

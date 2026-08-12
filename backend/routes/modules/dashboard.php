<?php

// use App\Http\Controllers\CookieController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->middleware('verified')->name('dashboard');

    // Reconciliación del feed de actividad reciente (fallback tras desconexión de WebSocket)
    Route::get('dashboard/activity-log', [DashboardController::class, 'activityLog'])
        ->middleware(['verified', 'role:admin,superadmin'])
        ->name('dashboard.activity-log');

    // Obtener cookie referral
    // Route::get('cookie', [CookieController::class, 'getReferral'])->name('cookie.get');
});

<?php

use App\Http\Controllers\KycController;
use Illuminate\Support\Facades\Route;

// Rutas del jugador
Route::middleware('auth')->prefix('kyc')->group(function () {
    Route::get('status',  [KycController::class, 'status'])->name('kyc.status');
    Route::post('submit', [KycController::class, 'submit'])->name('kyc.submit');
});

// Rutas de administración
Route::middleware(['auth', 'multiPermission:kyc.manage'])->prefix('kyc')->group(function () {
    Route::post('{document}/approve', [KycController::class, 'approve'])->name('kyc.approve');
    Route::post('{document}/reject',  [KycController::class, 'reject'])->name('kyc.reject');
});

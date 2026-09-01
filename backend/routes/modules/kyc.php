<?php

use App\Http\Controllers\KycController;
use Illuminate\Support\Facades\Route;

// Rutas del jugador
Route::middleware('auth')->prefix('kyc')->group(function () {
    Route::get('status',  [KycController::class, 'status'])->name('kyc.status');
    // throttle:10,1 — antes sin límite pese a aceptar hasta 3 archivos de
    // 10MB cada uno (front/back/selfie) por request, permitiendo spam de
    // subida de archivos (agotamiento de storage) desde una sesión válida.
    Route::post('submit', [KycController::class, 'submit'])->middleware('throttle:10,1')->name('kyc.submit');
});

// Rutas de administración
Route::middleware(['auth', 'multiPermission:kyc.manage'])->prefix('kyc')->group(function () {
    Route::post('{document}/approve', [KycController::class, 'approve'])->name('kyc.approve');
    Route::post('{document}/reject',  [KycController::class, 'reject'])->name('kyc.reject');
});

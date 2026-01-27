<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de autenticación para invitados (guest)
|--------------------------------------------------------------------------
| Estas rutas solo están disponibles cuando el usuario NO está autenticado.
| Aquí se maneja el registro, login y recuperación de contraseña.
*/

Route::middleware('guest')->group(function () {
    // Registro de usuario
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    // Login de usuario
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login')->defaults('inertiaOnly', true);
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Olvidé mi contraseña (formulario para solicitar reset link)
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    // Enviar email con link de recuperación
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    // Reset de contraseña (pantalla que recibe el token del correo)
    Route::get('resetPassword/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    // Guardar nueva contraseña en base de datos
    Route::post('resetPassword', [NewPasswordController::class, 'store'])->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Rutas de autenticación para usuarios logueados (auth)
|--------------------------------------------------------------------------
| Estas rutas solo están disponibles cuando el usuario YA está autenticado.
| Aquí se maneja la verificación de email, confirmación de contraseña,
| actualización de contraseña y cierre de sesión.
*/
Route::middleware('auth')->group(function () {
    // Mostrar aviso de verificación de email
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    // Verificar email mediante enlace firmado (enviado al correo)
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1']) // signed: valida URL | throttle: limita intentos
        ->name('verification.verify');

    // Reenviar email de verificación
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')->name('verification.send');

    // Confirmar contraseña (ej: acciones sensibles como eliminar cuenta)
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    // Actualizar contraseña (desde perfil/ajustes)
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Cerrar sesión (logout)
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

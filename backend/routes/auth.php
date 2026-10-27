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

Route::middleware('guest')->group(function() {
    // Registro de usuario. throttle:5,1 en el POST — antes sin límite: un
    // POST /register sin protección permite creación masiva de cuentas y
    // enumeración de emails/usernames ya en uso vía el mensaje de validación
    // "unique" (mismo criterio que login, que ya tenía RateLimiter propio).
    // register-by-ip (perDay) se apila encima: throttle:5,1 no frena un
    // ataque que espacia los intentos por debajo de 5/min — ver
    // AppServiceProvider::boot() para el detalle del límite diario.
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register')->defaults('inertiaOnly', true);
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware(['throttle:5,1', 'throttle:register-by-ip']);

    // Login de usuario. throttle:20,1 (más alto que register/reset — un
    // usuario legítimo puede reintentar varias veces por typo de contraseña)
    // como defensa en profundidad además del RateLimiter propio de
    // LoginRequest::authenticate() (5 intentos por login+IP): ese limiter
    // no frena un ataque de credential-stuffing que prueba muchos "login"
    // distintos desde la misma IP, porque su clave incluye el login.
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login')->defaults('inertiaOnly', true);
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:20,1');

    // Olvidé mi contraseña. throttle:5,1 — antes sin límite: permitía
    // enumerar emails registrados (respuesta distinta según exista o no) y
    // bombardear de correos de reset a un email ajeno.
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request')->defaults('inertiaOnly', true);
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email')->middleware('throttle:5,1');

    // Reset de contraseña (pantalla que recibe el token del correo)
    Route::get('resetPassword/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    // Guardar nueva contraseña. throttle:5,1 — limita fuerza bruta contra el
    // token de un solo uso enviado por correo.
    Route::post('resetPassword', [NewPasswordController::class, 'store'])->name('password.store')->middleware('throttle:5,1');
});

/*
|--------------------------------------------------------------------------
| Rutas de autenticación para usuarios logueados (auth)
|--------------------------------------------------------------------------
| Estas rutas solo están disponibles cuando el usuario YA está autenticado.
| Aquí se maneja la verificación de email, confirmación de contraseña,
| actualización de contraseña y cierre de sesión.
*/
Route::middleware('auth')->group(function() {
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

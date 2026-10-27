<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\UpdateUserPassword;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

/**
 * FortifyServiceProvider
 *
 * Configura Laravel Fortify para autenticación con Sanctum.
 */
class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Configurar Fortify
     */
    public function boot() : void
    {
        // Configuración para crear un nuevo usuario con Fortify
        Fortify::createUsersUsing(CreateNewUser::class);

        // Configuración para actualizar la contraseña de usuario con Fortify
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);

        // Las vistas de login y registro no son necesarias en un SPA (serán manejadas por el frontend)
        // Por lo tanto, las redirigimos o devolvemos un error 404
        Fortify::loginView(fn () => abort(404)); // O podrías redirigir a tu frontend SPA
        Fortify::registerView(fn () => abort(404)); // O redirigir a tu frontend SPA

        // Configurar las rutas de autenticación de Sanctum
        Fortify::authenticateUsing(fn ($request) => $request->user());
    }

    /**
     * Registrar servicios
     */
    public function register() : void
    {
        // Aquí se registran los servicios, si es necesario
    }
}

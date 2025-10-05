<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\UpdateUserPassword;
use Laravel\Fortify\Fortify;
use Illuminate\Support\ServiceProvider;

/**
 * FortifyServiceProvider
 *
 * Configura Laravel Fortify para autenticación.
 */
class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios
     */
    public function register(): void
    {
        //
    }

    /**
     * Configurar Fortify
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);

        Fortify::loginView(fn() => view('auth.login'));
        Fortify::registerView(fn() => view('auth.register'));
    }
}

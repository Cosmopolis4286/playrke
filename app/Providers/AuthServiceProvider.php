<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * AuthServiceProvider
 *
 * Registra políticas de autorización y gates de la aplicación.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * Políticas de autorización de los modelos.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Registrar Gates y políticas.
     */
    public function boot(): void
    {
        // Registrar políticas primero
        $this->registerPolicies();

        // Los Gates se definen después de que el servicio esté disponible
        $this->app->booted(function () {
            // Definir Gates para roles
            \Illuminate\Support\Facades\Gate::define('admin', function ($user) {
                $firstRole = $user->roles->first();
                return $firstRole && $firstRole->prefix === 'admin';
            });

            \Illuminate\Support\Facades\Gate::define('commerce', function ($user) {
                $firstRole = $user->roles->first();
                return $firstRole && in_array($firstRole->prefix, ['commerce', 'admin']);
            });

            \Illuminate\Support\Facades\Gate::define('treasurer', function ($user) {
                $firstRole = $user->roles->first();
                return $firstRole && in_array($firstRole->prefix, ['treasurer', 'admin']);
            });

            \Illuminate\Support\Facades\Gate::define('seller', function ($user) {
                $firstRole = $user->roles->first();
                return $firstRole && in_array($firstRole->prefix, ['seller', 'admin']);
            });

            \Illuminate\Support\Facades\Gate::define('customer', function ($user) {
                $firstRole = $user->roles->first();
                return $firstRole && $firstRole->prefix === 'customer';
            });

            \Illuminate\Support\Facades\Gate::define('player', function ($user) {
                $firstRole = $user->roles->first();
                return $firstRole && $firstRole->prefix === 'player';
            });
        });
    }
}

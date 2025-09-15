<?php

namespace App\Providers;

use App\Repositories\User\Roles\RoleRepository;
use App\Services\Games\Room\RoomTypeService;
use App\Services\RouteService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

/**
 * Proveedor de servicios principal de la aplicación.
 *
 * Se encarga de registrar singletons, forzar HTTPS en producción
 * y compartir datos globales con todas las vistas.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios de aplicación.
     *
     * @return void
     */
    public function register(): void
    {
        // Registrar singleton para RoomTypeService
        $this->app->singleton(RoomTypeService::class, fn($app) => new RoomTypeService());

        // Registrar singleton para RouteService
        $this->app->singleton(RouteService::class, fn($app) => new RouteService());
    }

    /**
     * Bootstrap de servicios de aplicación.
     *
     * Forzar HTTPS en producción y compartir el prefijo del rol principal
     * del usuario con todas las vistas.
     *
     * @return void
     */
    public function boot(): void
    {
        // Forzar HTTPS en producción
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Compartir prefix del rol principal en todas las vistas
        View::composer('*', function ($view): void {
            $prefix = null;

            if (Auth::check()) {
                $user = Auth::user();

                // Obtener el primer rol asignado al usuario
                $firstRoleId = $user->roles->first()?->id;

                if ($firstRoleId !== null) {
                    $prefix = RoleRepository::getValueById($firstRoleId, 'prefix');
                }
            }

            $view->with('creatorTypePrefix', $prefix);
        });
    }
}

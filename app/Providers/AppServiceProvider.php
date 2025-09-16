<?php

namespace App\Providers;

use App\Repositories\Users\Roles\RoleRepository;
use App\Services\Games\Room\RoomTypeService;
use App\Services\RouteService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Filesystem\Filesystem;

/**
 * AppServiceProvider
 *
 * Proveedor principal de la aplicación.
 * 
 * Funciones principales:
 *  - Registrar singletons y bindings necesarios
 *  - Forzar HTTPS en producción
 *  - Compartir datos globales con todas las vistas
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios en el contenedor de Laravel
     *
     * Aquí registramos singletons y bindings que deben estar disponibles
     * durante toda la ejecución de la aplicación.
     *
     * @return void
     */
    public function register(): void
    {
        // -------------------------------------------
        // Singletons de servicios de la aplicación
        // -------------------------------------------

        // Servicio para la lógica de tipos de salas de juego
        $this->app->singleton(RoomTypeService::class, fn($app) => new RoomTypeService());

        // Servicio para manejo de rutas o helpers globales de rutas
        $this->app->singleton(RouteService::class, fn($app) => new RouteService());

        // -------------------------------------------
        // Binding obligatorio para 'files'
        // -------------------------------------------
        // Esto evita el error "Target class [files] does not exist"
        // que Laravel puede lanzar al usar Filesystem en algunos servicios.
        $this->app->singleton('files', function () {
            return new Filesystem();
        });
    }

    /**
     * Bootstrap de la aplicación
     *
     * Este método se ejecuta después de que todos los servicios han sido
     * registrados. Aquí podemos:
     *  - Configurar URL seguras
     *  - Compartir variables globales con las vistas
     *
     * @return void
     */
    public function boot(): void
    {
        // -------------------------------------------
        // Forzar HTTPS en producción
        // -------------------------------------------
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // -------------------------------------------
        // Compartir prefijo del rol principal en todas las vistas
        // -------------------------------------------
        // Esto permite acceder a $creatorTypePrefix en cualquier componente
        // o vista Blade/Inertia, evitando repetir la lógica en cada controlador.
        View::composer('*', function ($view): void {
            $prefix = null;

            if (Auth::check()) {
                $user = Auth::user();

                // Obtener el primer rol asignado al usuario
                $firstRole = $user->roles->first();

                if ($firstRole !== null) {
                    // RoleRepository maneja la recuperación de datos del rol
                    $prefix = RoleRepository::findOrFail($firstRole->id, 'prefix');
                }
            }

            // Se comparte la variable con todas las vistas
            $view->with('creatorTypePrefix', $prefix);
        });
    }
}

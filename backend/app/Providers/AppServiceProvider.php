<?php

namespace App\Providers;

use App\Domain\Common\Status\StatusChanger;
use App\Repositories\BaseRepository;
use App\Services\Games\Room\RoomTypeService;
use App\Services\Route\RouteService;
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

        // Resolver para inyectar StatusChanger en cualquier BaseRepository
        $this->app->resolving(BaseRepository::class, function ($repo, $app) {
            if (method_exists($repo, 'setStatusChanger')) {
                $repo->setStatusChanger($app->make(StatusChanger::class));
            }
        });

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
        // DISABLED: Causing conflicts with Auth and View loading order
        // This can be enabled later if needed, but must be loaded after
        // all services are fully booted
        if ($this->app->has('view')) {
            View::composer('*', function ($view): void {
                $prefix = null;
                if (Auth::check()) {
                    $user = Auth::user();
                    $firstRole = $user->roles->first();
                    if ($firstRole !== null) {
                        $prefix = $firstRole->prefix;
                    }
                }
                $view->with('userRolePrefix', $prefix);
            });
        }
    }
}

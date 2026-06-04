<?php

namespace App\Providers;

use App\Domains\Common\Status\StatusChanger;
use App\Repositories\BaseRepository;
use App\Services\Route\RouteService;
use App\Support\EnvValidator;
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

        // -------------------------------------------
        // Binding de middleware 'tos' en el contenedor
        // -------------------------------------------
        // Necesario para que Laravel pueda resolver el alias antes de que
        // el router haya registrado los aliases de middleware.
        $this->app->bind('tos', \App\Http\Middleware\RequireTosAcceptance::class);
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
        // Validar variables de entorno críticas al startup
        // -------------------------------------------
        // Falla rápido y claro si falta o es inválida alguna variable.
        // Solo se ejecuta fuera de tests para no interferir con phpunit.
        if (!$this->app->runningUnitTests()) {
            EnvValidator::validate();
        }

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
            // Datos del navbar (billetera activa del usuario)
            View::composer('components.template.navbar', \App\Http\View\NavbarComposer::class);

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

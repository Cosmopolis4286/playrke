<?php

namespace App\Providers;

use App\Repositories\User\RoleRepository;
use App\Services\Games\Room\RoomTypeService;
use App\Services\RouteService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios de aplicación.
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
     */
    public function boot(): void
    {
        // Forzar HTTPS en producción
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Inyectar prefix de tipo creador en todas las vistas
        View::composer('*', function ($view): void {
            $prefix = null;

            if (Auth::check()) {
                $userTypeId = Auth::user()->user_type;
                $prefix = RoleRepository::getValueById($userTypeId, 'prefix');
            }

            $view->with('creatorTypePrefix', $prefix);
        });
    }
}

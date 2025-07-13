<?php

namespace App\Providers;

use App\Models\UserType;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Inyectar $creatorTypePrefix en todas las vistas
        View::composer('*', function ($view) {
            $prefix = null;

            if (Auth::check()) {
                $userTypeId = Auth::user()->user_type;
                $prefix = UserType::getValueById($userTypeId, 'prefix');
            }

            $view->with('creatorTypePrefix', $prefix);
        });
    }
}

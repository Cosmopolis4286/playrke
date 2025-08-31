<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Repositories\User\RoleRepository;
use Illuminate\Support\Facades\App;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Políticas de autorización de los modelos.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Registrar Gates y políticas.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Definir Gates dinámicamente usando RoleRepository
        $userTypeRepo = App::make(RoleRepository::class);

        // Gate para admin
        Gate::define('admin', fn($user) => $userTypeRepo->getValueById($user->user_type, 'prefix') === 'admin');

        // Gate para commerce (incluye admin si quieres jerarquía)
        Gate::define('commerce_id', fn($user) => in_array($userTypeRepo->getValueById($user->user_type, 'prefix'), ['commerce_id', 'admin']));

        // Puedes agregar más Gates aquí sin tocar el middleware
        // Ejemplo: Gate::define('manager', fn($user) => $userTypeRepo->getValueById($user->user_type, 'prefix') === 'manager');
    }
}

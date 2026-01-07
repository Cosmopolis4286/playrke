<?php

namespace App\Providers;

use App\Models\Users\Roles\Commerce\Commerce;
use App\Policies\Users\Roles\Commerce\CommercePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * Proveedor de autorización
 *
 * Registra políticas y gates para el sistema de roles.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * Políticas de autorización para los modelos.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Commerce::class => CommercePolicy::class,
    ];

    /**
     * Registra políticas y gates.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPolicies();

        $this->app->booted(function () {
            $this->defineRoleGate('admin');
            $this->defineRoleGate('commerce', ['commerce', 'admin']);
            $this->defineRoleGate('treasurer', ['treasurer', 'admin']);
            $this->defineRoleGate('seller', ['seller', 'admin']);
            $this->defineRoleGate('customer');
            $this->defineRoleGate('player');
        });
    }

    /**
     * Define un gate para un rol o múltiples roles.
     *
     * @param string $gateName Nombre del gate
     * @param string[]|null $allowedPrefixes Prefijos de rol permitidos (por defecto solo el gateName)
     * @return void
     */
    protected function defineRoleGate(string $gateName, array|null $allowedPrefixes = null): void
    {
        $prefixes = $allowedPrefixes ?? [$gateName];

        Gate::define($gateName, function ($user) use ($prefixes) {
            return $user->roles->contains(function ($role) use ($prefixes) {
                return in_array($role->prefix, $prefixes, true);
            });
        });
    }
}

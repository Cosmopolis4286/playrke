<?php

namespace App\Providers;

use App\Services\Auth\RoleAuthorizationService;
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
        \App\Models\Roles\Commerce::class => \App\Policies\Roles\Admin\AdminCommercePolicy::class,
        \App\Models\Roles\Customer::class => \App\Policies\Roles\Admin\AdminCustomerPolicy::class,
        \App\Models\Roles\Treasurer::class => \App\Policies\Roles\Admin\AdminTreasurerPolicy::class,
        \App\Models\Roles\Seller::class => \App\Policies\Roles\Seller\Manager\ManagerSellerPolicy::class,
        \App\Models\Prize::class => \App\Policies\Prizes\PrizePolicy::class,
        \App\Models\Game::class                                    => \App\Policies\Games\GamePolicy::class,
        \App\Models\Setting::class                                 => \App\Policies\SettingPolicy::class,
        \App\Domains\Games\Bingo\Models\Room\BingoRoom::class     => \App\Domains\Games\Bingo\Policies\BingoRoomPolicy::class,
        \App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic::class => \App\Domains\Games\Bingo\Policies\BingoRoomAutomaticPolicy::class,
        \App\Models\WalletMovement::class                     => \App\Policies\Wallet\WalletMovementPolicy::class,
        \App\Models\Permission::class                         => \App\Policies\Roles\Admin\PermissionPolicy::class,
        \App\Models\Module::class                             => \App\Policies\Roles\Admin\ModulePolicy::class,
        \App\Models\SidebarMenuVisibility::class               => \App\Policies\Roles\Admin\SidebarMenuVisibilityPolicy::class,
        \App\Models\Role::class                                => \App\Policies\Roles\Admin\RolePolicy::class,
        \App\Models\Roles\Player\External\ExternalPlayer::class => \App\Policies\Roles\Admin\ExternalPlayerPolicy::class,
        \App\Models\User::class                               => \App\Policies\UserPolicy::class,
        // JobsCenterPolicy no tiene un Model Eloquent único (vista compuesta
        // sobre scheduled_commands + jobs + failed_jobs) — se registra contra
        // su propia clase para poder usar $this->authorize('manage', JobsCenterPolicy::class).
        \App\Policies\System\JobsCenterPolicy::class          => \App\Policies\System\JobsCenterPolicy::class,
        // KycPolicy: mismo criterio (vista compuesta sobre KycDocument, no
        // un Model único por acción) — registrada contra su propia clase.
        \App\Policies\Roles\Admin\KycPolicy::class            => \App\Policies\Roles\Admin\KycPolicy::class,
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

        Gate::define(
            $gateName,
            fn ($user) => app(RoleAuthorizationService::class)->hasAnyRole($user, $prefixes)
        );
    }
}

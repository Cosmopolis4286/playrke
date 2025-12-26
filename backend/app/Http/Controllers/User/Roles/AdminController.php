<?php

namespace App\Http\Controllers\User\Roles;

use App\Http\Controllers\Controller;
use App\Repositories\Countries\Contracts\CountryRepositoryContract;
use App\Repositories\Currencies\Contracts\CurrencyRepositoryContract;
use App\Repositories\Games\Contracts\GameRepositoryContract;
use App\Repositories\Users\Roles\Commerce\Contracts\CommerceRepositoryContract;
use App\Repositories\Users\Roles\Customer\Contracts\CustomerRepositoryContract;
use App\Repositories\Users\Roles\Treasurer\Contracts\TreasurerRepositoryContract;
use App\Repositories\Users\Roles\Treasurer\Contracts\OtherTreasurersRepositoryContract;
use App\Repositories\Users\Shared\Contracts\TimeZoneRepositoryContract;
use App\Traits\Helpers\EncryptTrait;
use App\Traits\Users\UserTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controlador Admin refactorizado
 *
 * - Usa solo repositorios (no llama directamente a modelos)
 * - Todos los Snowflake IDs se serializan como strings
 * - Código consistente y mantenible
 */
class AdminController extends Controller
{
    use UserTrait, EncryptTrait;

    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(
        protected CommerceRepositoryContract $commerces,
        protected CustomerRepositoryContract $customers,
        protected TreasurerRepositoryContract $treasurers,
        protected OtherTreasurersRepositoryContract $otherTreasurers,
        protected CountryRepositoryContract $countries,
        protected CurrencyRepositoryContract $currencies,
        protected GameRepositoryContract $games,
        protected TimeZoneRepositoryContract $timeZones
    ) {}

    /**
     * Vista principal del dashboard de administrador
     */
    public function dashboard(): View
    {
        return view('modules.users.roles.admin.dashboard.index', [
            'title' => 'Dashboard Admin',
        ]);
    }

    /**
     * Vista de comercializadoras
     */
    public function commerces(): View
    {
        $commerces = $this->commerces->getAllWithRelations([
            'country:id,name',
            'user:id,email',
        ]);

        return view('modules.users.roles.admin.commerce.index', [
            'title' => 'Comercializadoras',
            'pageData' => [
                'currencies' => $this->currencies->getActive(),
                'commerces'  => $commerces,
                'countries' => $this->countries->getActive(),
                'games'     => $this->games->getActive(),
                'timeZones' => $this->timeZones->getActive(),
                'enableFormCrud' => true,
                'moduleFile' => 'modules/users/roles/admin/commerce/form/commerceForm.ts',
            ]
        ]);
    }

    /**
     * Vista de clientes
     */
    public function customers(): View
    {
        return view('modules.admin.customer.index', [
            'title'      => 'Clientes',
            'name'       => 'admin-customer',
            'customers'  => $this->customers->getAllWithRelations([
                'country:id,name',
                'user:id,email'
            ]),
            'countries'  => $this->countries->getActive(),
            'timeZones'  => $this->timeZones->getActive(),
            'currencies' => $this->currencies->getActive(),
        ]);
    }

    /**
     * Vista de tesoreros
     */
    public function treasurers(): View
    {
        return view('modules.admin.treasurer.index', [
            'title'      => 'Tesoreros',
            'name'       => 'admin-treasurer',
            'others'     => false,
            'treasurers' => $this->treasurers->getAllWithRelations([
                'country:id,name',
                'user:id,email'
            ]),
            'countries'  => $this->countries->getActive(),
            'timeZones'  => $this->timeZones->getActive(),
            'currencies' => $this->currencies->getActive(),
            'games'      => $this->games->getActive(),
        ]);
    }

    /**
     * Vista de otros tesoreros
     */
    public function otherTreasurers(): View
    {
        return view('modules.admin.treasurer.other-treasurers', [
            'title'           => 'Otros Tesoreros',
            'name'            => 'admin-other-treasurers',
            'others'          => true,
            'otherTreasurers' => $this->otherTreasurers->getAllWithRelations([
                'user:id,user_type,country,username',
                'user.country:id,name'
            ]),
            'currencies'      => $this->currencies->getActive(),
            'games'           => $this->games->getActive(),
        ]);
    }

    /**
     * Vista de subsidiarias
     */
    public function subsidiaries(): View
    {
        return view('modules.admin.subsidiary.index', [
            'title' => 'Subsidiarias',
        ]);
    }

    /**
     * Vista de usuarios
     */
    public function users(): View
    {
        return view('modules.admin.users.index', [
            'title' => 'Usuarios',
        ]);
    }

    /**
     * Vista de vendedores
     */
    public function sellers(): View
    {
        return view('modules.admin.seller.index', [
            'title' => 'Vendedores',
        ]);
    }

    /**
     * Obtiene información de usuarios según rol
     *
     * @param string $userRole Rol del usuario
     * @param string $exclude Modelo a excluir (opcional)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserInfo(string $userRole, string $exclude = '')
    {
        if ($userRole === 'admin') {
            return response()->json([]);
        }

        $excludedUsers = $this->resolveExcludedCreators($exclude);

        try {
            $users = match ($userRole) {
                'commerce' => $this->getFilteredUsers($this->commerces->getActiveCommerces(['name', 'user_id']), $excludedUsers),
                'customer' => $this->getFilteredUsers($this->customers->getAll(['name', 'user_id']), $excludedUsers),
                'treasurer' => $this->getFilteredUsers($this->treasurers->getAll(['name', 'user_id']), $excludedUsers),
                default => []
            };

            return response()->json($users);
        } catch (\Throwable $e) {
            Log::error('Error obteniendo información de usuarios', [
                'user_role' => $userRole,
                'exclude' => $exclude,
                'exception' => $e->getMessage()
            ]);

            return response()->json([], 500);
        }
    }

    /**
     * Filtra usuarios excluyendo los IDs especificados
     *
     * @param \Illuminate\Database\Eloquent\Collection $users
     * @param array $excludedUserIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getFilteredUsers($users, array $excludedUserIds)
    {
        if (empty($excludedUserIds)) {
            return $users;
        }

        return $users->filter(function ($user) use ($excludedUserIds) {
            return !in_array($user->user_id ?? null, $excludedUserIds);
        })->values();
    }

    /**
     * Obtiene usuarios creadores a excluir dinámicamente
     *
     * @param string $model Nombre del modelo
     * @return array IDs de usuarios a excluir
     */
    protected function resolveExcludedCreators(string $model): array
    {
        if (!$model) {
            return [];
        }

        $modelClass = "App\\Models\\{$model}";

        if (!class_exists($modelClass)) {
            Log::warning("Modelo no existe para exclusión: {$modelClass}");
            return [];
        }

        if (!method_exists($modelClass, 'getAllCreatorUser')) {
            return [];
        }

        try {
            return $modelClass::getAllCreatorUser();
        } catch (\Throwable $e) {
            Log::error("Error obteniendo creadores del modelo {$modelClass}", [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }
}

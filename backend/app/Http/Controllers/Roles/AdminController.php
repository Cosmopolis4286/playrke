<?php

namespace App\Http\Controllers\Roles;

use App\Contracts\CountryRepositoryInterface;
use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\GameRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerRepositoryInterface;
use App\Contracts\Roles\Treasurer\TreasurerRepositoryInterface;
use App\Contracts\Roles\Treasurer\OtherTreasurersRepositoryInterface;
use App\Contracts\TimeZoneRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Services\Roles\Admin\AdminService;
use App\Traits\Helpers\EncryptTrait;
use App\Traits\Users\UserTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
        protected AdminService $service,
        protected CommerceRepositoryInterface $commerces,
        protected CustomerRepositoryInterface $customers,
        protected TreasurerRepositoryInterface $treasurers,
        protected OtherTreasurersRepositoryInterface $otherTreasurers,
        protected CountryRepositoryInterface $countries,
        protected CurrencyRepositoryInterface $currencies,
        protected GameRepositoryInterface $games,
        protected TimeZoneRepositoryInterface $timeZones
    ) {}

    /**
     * Vista principal del dashboard de administrador
     */
    public function dashboard(): Response
    {
        return Inertia::render('modules/users/roles/admin/Dashboard', [
            'title' => 'Dashboard Admin',
        ]);
    }

    /**
     * Vista de comercializadoras
     */
    public function commerces(): View
    {
        $commerces = $this->commerces->getAllWithRelations(
            [
                'country:id,name',
                'user:id,email',
            ],
            ['id', 'country_id', 'user_id', 'name', 'phone', 'status']
        );

        return view('modules.users.roles.admin.commerce.index', [
            'title' => 'Comercializadoras',
            'pageData' => [
                'currencies' => $this->currencies->getActive(['id', 'iso_code', 'symbol', 'name', 'country_id']),
                'commerces'  => $commerces,
                'countries' => $this->countries->getActive(['id', 'name']),
                'games'     => $this->games->getActive(['id', 'name']),
                'timeZones' => $this->timeZones->getActive(['id', 'name', 'utc', 'country_id']),
                'enableFormCrud' => true,
                'moduleFile' => 'modules/roles/admin/commerce/form/commerceForm.ts',
            ]
        ]);
    }

    /**
     * Muestra el listado de comercializadoras usando Inertia.
     *
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function commercesNew(): Response|RedirectResponse
    {
        return $this->handleService(function () {
            $context = $this->service->context('commerce');

            return Inertia::render('Roles/Admin/Commerce/Index', $context);
        }, 'No se pudo cargar la lista de comercializadoras.');
    }

    /**
     * Vista de clientes
     *
     * Retorna la vista con todos los clientes y los datos necesarios
     * para el formulario (países, monedas, zonas horarias, juegos).
     */
    public function customers(): View
    {
        $customers = $this->customers->getAllWithRelations([
            'country:id,name',
            'user:id,email',
        ]);

        return view('modules.users.roles.admin.customer.index', [
            'title' => 'Clientes',
            'pageData' => [
                'currencies' => $this->currencies->getActive(),
                'customers'  => $customers,
                'countries'  => $this->countries->getActive(),
                'games'      => $this->games->getActive(),
                'timeZones'  => $this->timeZones->getActive(),
                'enableFormCrud' => true,
                'moduleFile' => 'modules/users/roles/customer/form/customerForm.ts',
            ]
        ]);
    }

    /**
     * Vista de tesoreros
     */
    public function treasurers(): View
    {
        return view('modules.users.roles.admin.treasurer.index', [
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
                'commerce' => $this->getFilteredUsers($this->commerces->getByStatus('ACTIVE', ['name', 'user_id']), $excludedUsers),
                'customer' => $this->getFilteredUsers($this->customers->getByStatus('ACTIVE', ['name', 'user_id']), $excludedUsers),
                'treasurer' => $this->getFilteredUsers($this->treasurers->getByStatus('ACTIVE', ['name', 'user_id']), $excludedUsers),
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

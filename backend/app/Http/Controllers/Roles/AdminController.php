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
use App\Traits\HashedTableDataTrait;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador Admin refactorizado
 *
 * - Usa solo repositorios (no llama directamente a modelos)
 * - Todos los Snowflake IDs se serializan como strings
 * - Código consistente y mantenible
 */
class AdminController extends Controller
{
    use HashedTableDataTrait;

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
     * Muestra el listado de tasas de cambio usando Inertia.
     *
     * @return Response
     */
    public function commerces(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $response = $this->service->context('commerces');

                $response['title'] = 'Comercializadoras';

                return Inertia::render('Roles/Admin/Commerce/Index', $response);
            },
            'No se pudo cargar la lista de comercializadoras.'
        );
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
}

<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Services\Roles\Admin\AdminService;
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
    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(protected AdminService $service) {}

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
     * Muestra el listado de comercializadoras usando Inertia.
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
     * Muestra el listado de clientes usando Inertia.
     *
     * @return Response
     */
    public function customers(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $response = $this->service->context('customers');

                $response['title'] = 'Clientes';

                return Inertia::render('Roles/Admin/Customer/Index', $response);
            },
            'No se pudo cargar la lista de clientes.'
        );
    }

    /**
     * Muestra el listado de vendedores usando Inertia.
     *
     * @return Response
     */
    public function sellers(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $response = $this->service->context('sellers');

                $response['title'] = 'Vendedores';

                return Inertia::render('Roles/Seller/Manager/Index', $response);
            },
            'No se pudo cargar la lista de vendedores.'
        );
    }

    /**
     * Muestra el listado de tesoreros usando Inertia.
     *
     * @return Response
     */
    public function treasurers(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $response = $this->service->context('treasurers');

                $response['title'] = 'Tesoreros';

                return Inertia::render('Roles/Admin/Treasurer/Index', $response);
            },
            'No se pudo cargar la lista de tesoreros.'
        );
    }
}

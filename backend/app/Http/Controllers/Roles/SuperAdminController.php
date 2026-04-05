<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Services\Roles\SuperAdmin\Dashboard\SuperAdminDashboardContextService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador SuperAdmin
 *
 * Gestiona el panel de superadministración del sistema.
 * Proporciona vista extendida del dashboard con métricas de sistema.
 */
class SuperAdminController extends Controller
{
    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(
        protected SuperAdminDashboardContextService $dashboardContextService
    ) {}

    /**
     * Vista principal del dashboard de superadministrador
     */
    public function dashboard(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $context = $this->dashboardContextService->dashboardContext();
                $response = $context->toView();
                $response['title'] = 'SuperAdmin Dashboard';

                return Inertia::render('Roles/SuperAdmin/Dashboard/Index', $response);
            },
            'No se pudo cargar el dashboard de superadministrador.'
        );
    }
}

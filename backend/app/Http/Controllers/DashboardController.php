<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la página principal / dashboard.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly \App\Services\Roles\Admin\AdminContextService $service,
    ) {}

    /**
     * Mostrar dashboard principal.
     *
     * @return Response
     */
    public function index(): Response
    {
        // Role::prefix se persiste siempre en mayúsculas (ver Role::setPrefixAttribute),
        // se normaliza aquí a minúsculas para poder mapearlo a la carpeta de vistas.
        $rolePrefix = strtolower(Auth::user()?->roles?->first()?->prefix ?? 'user');

        return $this->handleServiceInertia(
            function () use ($rolePrefix) {
                // Mapeo de prefijos a nombres de carpetas (casos especiales)
                $viewFolder = match ($rolePrefix) {
                    'superadmin' => 'SuperAdmin',
                    default => ucfirst($rolePrefix),
                };
                $view = "Roles/{$viewFolder}/Dashboard/Index";

                try {
                    $context = $this->service->resolve($rolePrefix . '_dashboard')->toView();
                } catch (\Throwable $e) {
                    Log::error('Error cargando dashboard', [
                        'role' => $rolePrefix,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $context = [];
                }

                return Inertia::render($view, array_merge($context, [
                    'title'    => 'Dashboard',
                    'userRole' => $rolePrefix,
                ]));
            },
            'No se pudo cargar el dashboard.'
        );
    }
}

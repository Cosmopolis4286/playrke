<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Roles\ContextResolverService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la página principal / dashboard.
 */
class DashboardController extends Controller
{
    /** Prefijos de rol cuya carpeta de vista no coincide con ucfirst($prefix). */
    private const VIEW_FOLDER_OVERRIDES = [
        'superadmin' => 'SuperAdmin',
    ];

    public function __construct(
        private readonly ContextResolverService $service,
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
                $view = "Roles/" . $this->viewFolderFor($rolePrefix) . "/Dashboard/Index";

                try {
                    $context = $this->service->resolve($rolePrefix . '_dashboard')->toView();
                } catch (DomainException $e) {
                    Log::warning('Dashboard sin contexto implementado para este rol', [
                        'role' => $rolePrefix,
                    ]);
                    $context = [];
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

    private function viewFolderFor(string $rolePrefix): string
    {
        return self::VIEW_FOLDER_OVERRIDES[$rolePrefix] ?? ucfirst($rolePrefix);
    }
}

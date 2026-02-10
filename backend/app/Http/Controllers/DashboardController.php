<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
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
        $rolePrefix = Auth::user()?->roles?->first()?->prefix ?? 'user';

        return $this->handleServiceInertia(
            fn() => Inertia::render("Roles/" . ucfirst($rolePrefix) . "/Dashboard/Index", [
                'title' => 'Dashboard',
                'userRole' => $rolePrefix,
            ]),
            'No se pudo cargar el dashboard.'
        );
    }
}

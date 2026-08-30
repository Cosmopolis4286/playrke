<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\DashboardWidgetVisibility;
use App\Services\Dashboard\DashboardWidgetVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración de visibilidad de widgets del dashboard por
 * rol (StatCards, conteos de entidad, accesos rápidos de Admin/SuperAdmin).
 *
 * Protegido por middleware `role:superadmin` en la ruta
 * (role_protected_routes()) y además por DashboardWidgetVisibilityPolicy —
 * mismo patrón de doble capa que SidebarMenuVisibilityController.
 */
final class DashboardWidgetVisibilityController extends Controller
{
    public function __construct(
        protected readonly DashboardWidgetVisibilityService $service,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', DashboardWidgetVisibility::class);

        return Inertia::render('Roles/Admin/DashboardWidgets/Index', [
            'items'      => $this->service->getCatalog(),
            'roles'      => $this->service->getRoles(),
            'visibility' => $this->service->getVisibilityMatrix(),
        ]);
    }

    public function fetchList(): JsonResponse
    {
        $this->authorize('viewAny', DashboardWidgetVisibility::class);

        return response()->json([
            'items'      => $this->service->getCatalog(),
            'roles'      => $this->service->getRoles(),
            'visibility' => $this->service->getVisibilityMatrix(),
        ]);
    }

    public function saveChanges(Request $request): JsonResponse
    {
        $this->authorize('update', DashboardWidgetVisibility::class);

        $validated = $request->validate([
            'changes'                 => 'required|array',
            'changes.*.item_key'      => 'required|string',
            'changes.*.role_id'       => 'required|string|exists:roles,id',
            'changes.*.is_visible'    => 'required|boolean',
        ]);

        $this->service->applyChanges($validated['changes']);

        return response()->json([
            'message' => 'Visibilidad del dashboard actualizada correctamente',
        ]);
    }
}

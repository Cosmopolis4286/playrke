<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\SidebarMenuVisibility;
use App\Services\Roles\Admin\SidebarMenuVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración de visibilidad del sidebar por rol.
 *
 * Protegido por middleware `role:superadmin` en la ruta
 * (role_protected_routes()) y además por SidebarMenuVisibilityPolicy —
 * mismo patrón de doble capa que ModuleController/PermissionController.
 */
final class SidebarMenuVisibilityController extends Controller
{
    public function __construct(
        protected readonly SidebarMenuVisibilityService $service,
    ) {}

    /**
     * Mostrar la página de gestión de visibilidad del sidebar.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', SidebarMenuVisibility::class);

        return Inertia::render('Roles/Admin/SidebarMenu/Index', [
            'items'      => $this->service->getMenuItems(),
            'roles'      => $this->service->getRoles(),
            'visibility' => $this->service->getVisibilityMatrix(),
        ]);
    }

    /**
     * Contexto completo para la matriz (recarga sin reload de página).
     */
    public function fetchList(): JsonResponse
    {
        $this->authorize('viewAny', SidebarMenuVisibility::class);

        return response()->json([
            'items'      => $this->service->getMenuItems(),
            'roles'      => $this->service->getRoles(),
            'visibility' => $this->service->getVisibilityMatrix(),
        ]);
    }

    /**
     * Guardar un lote de cambios de visibilidad.
     */
    public function saveChanges(Request $request): JsonResponse
    {
        $this->authorize('update', SidebarMenuVisibility::class);

        $validated = $request->validate([
            'changes'                 => 'required|array',
            'changes.*.item_key'      => 'required|string',
            'changes.*.role_id'       => 'required|string|exists:roles,id',
            'changes.*.is_visible'    => 'required|boolean',
        ]);

        $this->service->applyChanges($validated['changes']);

        return response()->json([
            'message' => 'Visibilidad del menú actualizada correctamente',
        ]);
    }
}

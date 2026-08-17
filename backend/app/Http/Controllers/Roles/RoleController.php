<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\Roles\Admin\RoleHierarchyService;
use App\Services\Roles\Admin\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de administración de Roles (metadatos + activar/desactivar).
 * Protegido por middleware `role:superadmin,admin` en la ruta
 * (role_protected_routes()) y además por RolePolicy, que es quien aplica el
 * "techo" real: un admin no puede gestionar el rol 'superadmin' ni el rol
 * 'admin' (ver RolePolicy::update()) — mismo patrón que PermissionController.
 */
final class RoleController extends Controller
{
    public function __construct(
        protected readonly RoleService $service,
        protected readonly RoleHierarchyService $hierarchyService,
    ) {}

    public function fetchList(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return response()->json([
            'roles' => $this->service->getAll(),
        ]);
    }

    public function hierarchy(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return response()->json([
            'tree' => $this->hierarchyService->buildTree(),
        ]);
    }

    public function update(Request $request, string $role): JsonResponse
    {
        $roleModel = Role::findOrFail($role);
        $this->authorize('update', $roleModel);

        $validated = $request->validate([
            'description' => 'sometimes|required|string|max:255',
            'sort_order' => 'sometimes|required|integer|min:0|max:65535',
            'icon' => 'sometimes|nullable|string|max:50',
            'color' => 'sometimes|nullable|string|max:20',
            'status' => 'sometimes|required|boolean',
        ]);

        // El switch de status en RoleHierarchyTree.vue reusa este mismo
        // endpoint (en vez de PUT .../status) porque ya viaja junto al resto
        // de metadatos del modal de edición del árbol — RoleManagerModal.vue
        // sigue usando PUT .../status (toggleStatus) para su switch inline.
        if (array_key_exists('status', $validated) && $roleModel->name === 'superadmin') {
            abort(422, 'El rol superadmin no puede desactivarse.');
        }

        $updated = $this->service->updateMeta($role, $validated);

        return response()->json([
            'message' => 'Rol actualizado correctamente',
            'role' => $updated,
        ]);
    }

    public function toggleStatus(string $role): JsonResponse
    {
        $roleModel = Role::findOrFail($role);
        $this->authorize('update', $roleModel);

        $updated = $this->service->toggleStatus($role);

        return response()->json([
            'message' => 'Rol actualizado correctamente',
            'role' => $updated,
        ]);
    }
}

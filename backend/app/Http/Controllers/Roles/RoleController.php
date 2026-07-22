<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\Roles\Admin\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de administración de Roles (metadatos + activar/desactivar).
 * Protegido por middleware `role:superadmin` en la ruta
 * (role_protected_routes()) y además por RolePolicy — mismo patrón que
 * PermissionController.
 */
final class RoleController extends Controller
{
    public function __construct(
        protected readonly RoleService $service,
    ) {}

    public function fetchList(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return response()->json([
            'roles' => $this->service->getAll(),
        ]);
    }

    public function update(Request $request, string $role): JsonResponse
    {
        $roleModel = Role::findOrFail($role);
        $this->authorize('update', $roleModel);

        $request->validate([
            'description' => 'required|string|max:255',
        ]);

        $updated = $this->service->updateDescription($role, $request->string('description')->toString());

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

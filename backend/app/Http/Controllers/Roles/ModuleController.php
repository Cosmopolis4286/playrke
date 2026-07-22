<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Services\Roles\Admin\ModuleService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de administración de Módulos del sistema (feature flags del
 * sidebar). Protegido por middleware `role:superadmin` en la ruta
 * (role_protected_routes()) y además por ModulePolicy — mismo patrón que
 * PermissionController.
 */
final class ModuleController extends Controller
{
    public function __construct(
        protected readonly ModuleService $service,
    ) {}

    public function fetchList(): JsonResponse
    {
        $this->authorize('viewAny', Module::class);

        return response()->json([
            'modules' => $this->service->getAll(),
        ]);
    }

    public function toggleStatus(string $module): JsonResponse
    {
        $moduleModel = Module::findOrFail($module);
        $this->authorize('update', $moduleModel);

        $updated = $this->service->toggleStatus($module);

        return response()->json([
            'message' => 'Módulo actualizado correctamente',
            'module'  => $updated,
        ]);
    }
}

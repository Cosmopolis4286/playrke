<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Services\Roles\Admin\PermissionService;
use App\Services\Roles\Admin\PermissionContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración de Permisos.
 *
 * Solo accesible por SuperAdmin para gestionar permisos del sistema.
 */
final class PermissionController extends Controller
{
    public function __construct(
        protected readonly PermissionService $service,
        protected readonly PermissionContextService $contextService
    ) {}

    /**
     * Mostrar vista de permisos.
     */
    public function index(): Response
    {
        $context = $this->contextService->permissionContext();
        
        return Inertia::render('Roles/Admin/Permission/Index', [
            'permissions' => [
                'rows' => $context->permissions['rows'] ?? [],
                'pagination' => $context->pagination,
            ],
            'roles' => $context->roles,
        ]);
    }

    /**
     * Obtener lista de permisos para la tabla.
     */
    public function fetchList(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $data = $this->service->getPermissionsWithPagination($perPage);

        return response()->json([
            'permissions' => [
                'rows' => $data['permissions'],
                'pagination' => $data['pagination'],
            ],
        ]);
    }

    /**
     * Obtener permisos de un rol específico.
     */
    public function getRolePermissions(string $roleId): JsonResponse
    {
        $rolePermissions = $this->service->getPermissionsByRole($roleId);
        
        return response()->json([
            'role' => [
                'id' => $rolePermissions->roleId,
                'name' => $rolePermissions->roleName,
                'prefix' => $rolePermissions->rolePrefix,
                'permissions' => $rolePermissions->permissions,
            ],
        ]);
    }

    /**
     * Asignar permisos a un rol.
     */
    public function assignPermissions(Request $request, string $roleId): JsonResponse
    {
        $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'string',
        ]);

        $this->service->assignPermissionsToRole($roleId, $request->permission_ids);

        return response()->json([
            'message' => 'Permisos asignados correctamente',
        ]);
    }

    /**
     * Revocar permisos de un rol.
     */
    public function revokePermissions(Request $request, string $roleId): JsonResponse
    {
        $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'string',
        ]);

        $this->service->revokePermissionsFromRole($roleId, $request->permission_ids);

        return response()->json([
            'message' => 'Permisos revocados correctamente',
        ]);
    }

    /**
     * Crear un nuevo permiso.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'guard_name' => 'string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $permission = $this->service->createPermission($request->all());

        return response()->json([
            'message' => 'Permiso creado correctamente',
            'permission' => $permission,
        ]);
    }

    /**
     * Actualizar un permiso.
     */
    public function update(Request $request, string $permissionId): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permissionId,
            'guard_name' => 'string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $permission = $this->service->updatePermission($permissionId, $request->all());

        return response()->json([
            'message' => 'Permiso actualizado correctamente',
            'permission' => $permission,
        ]);
    }

    /**
     * Eliminar un permiso.
     */
    public function delete(string $permissionId): JsonResponse
    {
        $this->service->deletePermission($permissionId);

        return response()->json([
            'message' => 'Permiso eliminado correctamente',
        ]);
    }

    /**
     * Obtener todos los roles con sus permisos.
     */
    public function getRolesWithPermissions(): JsonResponse
    {
        $roles = $this->service->getRolesWithPermissions();

        return response()->json([
            'roles' => $roles,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Services\Roles\Admin\ModuleService;
use App\Services\Roles\Admin\PermissionContextService;
use App\Services\Roles\Admin\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración de Permisos.
 *
 * Protegido por middleware `role:superadmin` en la ruta (role_protected_routes())
 * y además por PermissionPolicy — capa de defensa independiente, consistente
 * con el resto de policies del proyecto (WalletMovementPolicy, GamePolicy).
 */
final class PermissionController extends Controller
{
    public function __construct(
        protected readonly PermissionService $service,
        protected readonly PermissionContextService $contextService,
        protected readonly ModuleService $moduleService,
    ) {
    }

    /**
     * Asignar permisos a un rol.
     */
    public function assignPermissions(Request $request, string $roleId) : JsonResponse
    {
        $this->authorize('manageAssignments', Permission::class);

        $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'string',
        ]);

        try {
            $this->service->assignPermissionsToRole($roleId, $request->permission_ids);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Permisos asignados correctamente',
        ]);
    }

    /**
     * Copiar los permisos no-sistema de un rol origen hacia un rol destino.
     * `replace=true` deja al destino con EXACTAMENTE los permisos del origen
     * (revoca lo que no coincida); por defecto es merge (solo agrega).
     */
    public function copyToRole(Request $request, string $role, string $sourceRole) : JsonResponse
    {
        $this->authorize('manageAssignments', Permission::class);

        $replace = $request->boolean('replace');

        try {
            $count = $this->service->copyPermissionsBetweenRoles($sourceRole, $role, $replace);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        $verb = $replace ? 'reemplazaron' : 'copiaron';

        return response()->json([
            'message' => "Se {$verb} {$count} permisos correctamente",
        ]);
    }

    /**
     * Eliminar un permiso.
     */
    public function delete(string $permissionId) : JsonResponse
    {
        $permission = Permission::findOrFail($permissionId);
        $this->authorize('delete', $permission);

        $this->service->deletePermission($permissionId);

        return response()->json([
            'message' => 'Permiso eliminado correctamente',
        ]);
    }

    /**
     * Obtener todos los permisos para la matriz (sin paginación).
     */
    public function fetchList() : JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        return response()->json([
            'permissions' => [
                'rows' => $this->service->getAllForMatrix(),
            ],
        ]);
    }

    /**
     * Obtener permisos de un rol específico.
     */
    public function getRolePermissions(string $roleId) : JsonResponse
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
     * Obtener todos los roles con sus permisos.
     */
    public function getRolesWithPermissions() : JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $roles = $this->service->getRolesWithPermissions();

        return response()->json([
            'roles' => $roles,
        ]);
    }

    /**
     * Mostrar vista de permisos.
     *
     * También carga los Módulos del sistema: viven en la misma pantalla
     * (/permissions) porque ambos son configuración global de acceso
     * gestionada solo por superadmin, aunque son conceptos distintos
     * (Permission = autorización por rol, Module = disponibilidad del
     * sidebar para todos).
     */
    public function index() : Response
    {
        $this->authorize('viewAny', Permission::class);

        $context = $this->contextService->permissionContext();

        return Inertia::render('Roles/Admin/Permission/Index', [
            'permissions' => [
                'rows' => $context->permissions['rows'] ?? [],
                'pagination' => $context->pagination,
            ],
            'roles' => $context->roles,
            'modules' => $this->moduleService->getAll(),
        ]);
    }

    /**
     * Revocar permisos de un rol.
     */
    public function revokePermissions(Request $request, string $roleId) : JsonResponse
    {
        $this->authorize('manageAssignments', Permission::class);

        $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'string',
        ]);

        try {
            $this->service->revokePermissionsFromRole($roleId, $request->permission_ids);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Permisos revocados correctamente',
        ]);
    }

    /**
     * Guardar en lote todos los cambios pendientes de la matriz de permisos
     * (asignaciones y revocaciones de varios roles) en una sola transacción.
     */
    public function saveMatrix(Request $request) : JsonResponse
    {
        $this->authorize('manageAssignments', Permission::class);

        $validated = $request->validate([
            'changes' => 'required|array',
            'changes.*.role_id' => 'required|string|exists:roles,id',
            'changes.*.permission_id' => 'required|string|exists:permissions,id',
            'changes.*.granted' => 'required|boolean',
        ]);

        $this->service->applyMatrixChanges($validated['changes']);

        return response()->json([
            'message' => 'Cambios de permisos guardados correctamente',
        ]);
    }

    /**
     * Crear un nuevo permiso.
     */
    public function store(Request $request) : JsonResponse
    {
        $this->authorize('create', Permission::class);

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
    public function update(Request $request, string $permissionId) : JsonResponse
    {
        $permission = Permission::findOrFail($permissionId);
        $this->authorize('update', $permission);

        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permissionId,
            'guard_name' => 'string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $updated = $this->service->updatePermission($permissionId, $request->all());

        return response()->json([
            'message' => 'Permiso actualizado correctamente',
            'permission' => $updated,
        ]);
    }
}

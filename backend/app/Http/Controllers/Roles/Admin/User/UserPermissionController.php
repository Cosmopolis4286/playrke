<?php

namespace App\Http\Controllers\Roles\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Roles\Admin\User\UserPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestión de permisos específicos por usuario individual.
 *
 * Solo superadmin (ver UserPolicy::managePermissions() + middleware
 * role:superadmin en la ruta) — distinto de PermissionController, que
 * gestiona la matriz rol×permiso, no permisos directos de una persona.
 */
final class UserPermissionController extends Controller
{
    public function __construct(private readonly UserPermissionService $service) {}

    /**
     * Permisos actuales del usuario (heredados por rol + específicos) y
     * catálogo completo de permisos disponibles.
     */
    public function index(User $user): JsonResponse
    {
        $this->authorize('managePermissions', $user);

        return response()->json($this->service->getUserPermissions($user));
    }

    /**
     * Reemplaza el set completo de permisos específicos del usuario.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('managePermissions', $user);

        $request->validate([
            'permissions'   => 'present|array',
            'permissions.*' => 'string',
        ]);

        $this->service->syncSpecificPermissions($user, $request->input('permissions'));

        return response()->json(['message' => 'Permisos actualizados correctamente']);
    }
}

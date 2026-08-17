<?php

namespace App\Http\Controllers\Roles;

use App\DTOs\Roles\Admin\Administrator\AdministratorStoreDTO;
use App\DTOs\Roles\Admin\Administrator\AdministratorUpdateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Admin\Administrator\AdministratorStoreRequest;
use App\Http\Requests\Roles\Admin\Administrator\AdministratorUpdateRequest;
use App\Models\User;
use App\Services\Roles\Admin\AdministratorService;
use App\Services\Roles\Admin\AdministratorContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración de Administradores.
 *
 * Doblemente protegido: middleware `role:superadmin` en la ruta
 * (role_protected_routes()) y UserPolicy::createAdministrator /
 * manageAdministrator en cada acción — misma capa independiente que
 * RoleController/PermissionController, para que la autorización no dependa
 * solo del middleware de ruta.
 */
final class AdministratorController extends Controller
{
    public function __construct(
        protected readonly AdministratorService $service,
        protected readonly AdministratorContextService $contextService
    ) {}

    /**
     * Mostrar vista de administradores.
     */
    public function index(): Response
    {
        $context = $this->contextService->administratorContext();

        return Inertia::render('Roles/Admin/Administrator/Index', [
            'admins'    => [
                'rows'       => $context->admins['rows'] ?? [],
                'columns'    => $context->admins['columns'] ?? [],
                'pagination' => $context->pagination,
            ],
            'countries' => $context->countries,
            'roles'     => $context->roles,
        ]);
    }

    /**
     * Listado paginado de administradores (JSON). Server-side real: search,
     * role_name, country_name y status filtran contra todo el catálogo de
     * administradores, no solo la página cargada en memoria.
     */
    public function fetchList(Request $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {
            $filters = $request->only(['search', 'role_name', 'country_name', 'status']);
            $page    = (int) $request->get('page', 1);

            $result = $this->service->fetchList($filters, $page);

            return [
                'data' => $result['admins']['rows'] ?? [],
                'meta' => $result['pagination'],
            ];
        });
    }

    /**
     * Alta de un nuevo administrador.
     */
    public function store(AdministratorStoreRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'message' => 'Administrador registrado correctamente',
                'user'    => $this->service->store($request->toDTO(AdministratorStoreDTO::class)),
            ]
        );
    }

    /**
     * Actualizar datos básicos de un administrador.
     */
    public function update(AdministratorUpdateRequest $request, User $user): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'message' => 'Administrador actualizado correctamente',
                'user'    => tap($user, fn($u) => $this->service->update($u, $request->toDTO(AdministratorUpdateDTO::class))),
            ]
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de administrador.
     */
    public function toggleStatus(User $user): JsonResponse
    {
        $this->authorize('manageAdministrator', $user);

        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($user)
        );
    }

    /**
     * Eliminación lógica (soft delete) de administrador.
     */
    public function delete(User $user): JsonResponse
    {
        $this->authorize('manageAdministrator', $user);

        return $this->handleServiceJson(
            fn() => $this->service->delete($user)
        );
    }

    /**
     * Eliminación definitiva (hard delete) de administrador.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('manageAdministrator', $user);

        return $this->handleServiceJson(
            fn() => $this->service->destroy($user)
        );
    }

    /**
     * Restablecer contraseña de administrador.
     */
    public function resetPassword(User $user): JsonResponse
    {
        $this->authorize('manageAdministrator', $user);

        return $this->handleServiceJson(
            fn() => ['password' => $this->service->resetPassword($user)]
        );
    }
}

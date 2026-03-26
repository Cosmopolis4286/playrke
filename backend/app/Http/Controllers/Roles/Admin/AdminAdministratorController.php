<?php

namespace App\Http\Controllers\Roles\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Roles\Admin\Administrator\AdministratorService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de administración de Administradores.
 *
 * Solo accesible por SuperAdmin para gestionar otros administradores.
 */
final class AdminAdministratorController extends Controller
{
    public function __construct(
        protected readonly AdministratorService $service
    ) {}

    /**
     * Listado paginado de administradores (JSON).
     */
    public function fetchList(): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->fetchList()]
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de administrador.
     */
    public function toggleStatus(User $user): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($user)
        );
    }

    /**
     * Eliminación lógica (soft delete) de administrador.
     */
    public function delete(User $user): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($user)
        );
    }

    /**
     * Eliminación definitiva (hard delete) de administrador.
     */
    public function destroy(User $user): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($user)
        );
    }

    /**
     * Restablecer contraseña de administrador.
     */
    public function resetPassword(User $user): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->resetPassword($user)
        );
    }
}

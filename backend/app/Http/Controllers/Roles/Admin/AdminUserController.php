<?php

namespace App\Http\Controllers\Roles\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Roles\Admin\User\AdminUserService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de administración de Usuarios.
 *
 * Acciones disponibles: list, fetchList, toggleStatus, delete, destroy, resetPassword.
 * La creación se delega al flujo específico del rol seleccionado desde el frontend.
 */
final class AdminUserController extends Controller
{
    public function __construct(
        protected readonly AdminUserService $service
    ) {}

    /**
     * Listado paginado de usuarios (JSON).
     */
    public function fetchList(): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->fetchList()]
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE).
     */
    public function toggleStatus(User $user): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($user)
        );
    }

    /**
     * Eliminación lógica (soft delete).
     */
    public function delete(User $user): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($user)
        );
    }

    /**
     * Eliminación definitiva (hard delete).
     */
    public function destroy(User $user): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($user)
        );
    }

    /**
     * Resetear contraseña.
     */
    public function resetPassword(User $user): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->resetPassword($user),
            ]
        );
    }
}

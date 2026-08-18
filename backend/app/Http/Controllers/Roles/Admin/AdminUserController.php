<?php

namespace App\Http\Controllers\Roles\Admin;

use App\DTOs\Roles\Admin\User\AdminUserUpdateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Admin\User\AdminUserUpdateRequest;
use App\Models\User;
use App\Services\Roles\Admin\User\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * Listado paginado de usuarios (JSON). Server-side real: search, role_name,
     * country_name, status y has_specific_permissions filtran contra TODO el
     * catálogo de usuarios, no solo la página cargada en memoria.
     */
    public function fetchList(Request $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request) {
            $filters = $request->only(['search', 'role_name', 'country_name', 'status', 'has_specific_permissions', 'last_login_before']);
            $page    = (int) $request->get('page', 1);

            $result = $this->service->fetchList($filters, $page);

            return [
                'data' => $result['rows'],
                'meta' => $result['pagination'],
            ];
        });
    }

    /**
     * Exporta a CSV todos los usuarios que coincidan con los filtros actuales
     * (sin límite de paginación) — mismo patrón que
     * ExternalPlayerAdminController::export().
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['search', 'role_name', 'country_name', 'status', 'has_specific_permissions']);

        return response()->streamDownload(function () use ($filters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nombre', 'Apellido', 'Username', 'Email', 'Rol', 'País', 'Estado', 'Permisos individuales', 'Registrado', 'Última conexión']);

            foreach ($this->service->exportForCsv($filters) as $user) {
                fputcsv($handle, [
                    $user->first_name,
                    $user->last_name,
                    $user->username,
                    $user->email,
                    $user->roles->first()?->name,
                    $user->country?->name,
                    $user->status,
                    $user->specificPermissionGrants()->active()->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count(),
                    $user->created_at?->format('Y-m-d H:i:s'),
                    $user->last_login_at?->format('Y-m-d H:i:s') ?? 'Nunca',
                ]);
            }

            fclose($handle);
        }, 'usuarios-' . now()->format('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Ficha consolidada de un usuario (datos básicos, wallet, nivel/XP,
     * últimos eventos de auditoría) para el modal de detalle.
     */
    public function show(User $user): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->getUserDetail($user)
        );
    }

    /**
     * Historial de auditoría paginado del usuario (botón "Cargar más" en la
     * ficha, más allá del tope inicial que trae show()).
     */
    public function auditHistory(User $user, Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->getUserAuditHistory($user, (int) $request->get('page', 1))
        );
    }

    /**
     * Actualizar datos básicos (y opcionalmente el rol) de un usuario.
     */
    public function update(AdminUserUpdateRequest $request, User $user): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->update($user, $request->toDTO(AdminUserUpdateDTO::class))
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
     * Alternar estado de varios usuarios en una sola transacción.
     */
    public function toggleStatusBulk(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        return $this->handleServiceJson(
            fn() => $this->service->toggleStatusBulk($request->input('ids'))
        );
    }

    /**
     * Eliminación lógica de varios usuarios en una sola transacción.
     */
    public function deleteBulk(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        return $this->handleServiceJson(
            fn() => $this->service->deleteBulk($request->input('ids'))
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

    /**
     * Cargar saldo en wallet del usuario.
     */
    public function loadWalletBalance(User $user): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'message' => $this->service->loadWalletBalance(
                    $user,
                    request('amount'),
                    request('reason'),
                )
            ]
        );
    }

    /**
     * Obtener balance de wallet del usuario.
     */
    public function getWalletBalance(User $user): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->getWalletBalance($user)
        );
    }

    /**
     * Otorgar XP manualmente al usuario.
     */
    public function grantXp(User $user): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->grantXp(
                $user,
                (int) request('amount'),
                request('reason'),
            )
        );
    }
}

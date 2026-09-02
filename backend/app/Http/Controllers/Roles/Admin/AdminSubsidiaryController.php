<?php

namespace App\Http\Controllers\Roles\Admin;

use App\Http\Controllers\Controller;

use App\DTOs\Roles\Admin\Subsidiary\AdminSubsidiaryDTO;
use App\Http\Requests\Roles\Admin\Subsidiary\AdminSubsidiaryRequest;
use App\Models\Roles\Subsidiary;
use App\Services\Roles\Admin\Subsidiary\AdminSubsidiaryService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de administración de Sucursales.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class AdminSubsidiaryController extends Controller
{
    /**
     * @param AdminSubsidiaryService $service
     */
    public function __construct(
        protected readonly AdminSubsidiaryService $service
    ) {
        $this->authorizeResource(Subsidiary::class, 'subsidiary');
    }

    /**
     * Crear una sucursal.
     *
     * @param AdminSubsidiaryRequest $request
     * @return JsonResponse
     */
    public function store(AdminSubsidiaryRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->create(
                    $request->toDTO(AdminSubsidiaryDTO::class)
                ),
            ]
        );
    }

    /**
     * Listado de sucursales
     *
     * @return JsonResponse
     */
    public function fetchList(): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->fetchList()]
        );
    }

    /**
     * Mostrar detalle de una sucursal.
     *
     * @param Subsidiary $subsidiary
     * @return JsonResponse
     */
    public function show(Subsidiary $subsidiary): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->show($subsidiary),
            ]
        );
    }

    /**
     * Actualizar una sucursal.
     *
     * @param AdminSubsidiaryRequest $request
     * @param Subsidiary $subsidiary
     * @return JsonResponse
     */
    public function update(AdminSubsidiaryRequest $request, Subsidiary $subsidiary): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->update(
                $subsidiary,
                $request->toDTO(AdminSubsidiaryDTO::class)
            )
        );
    }

    /**
     * Eliminar una sucursal (soft delete).
     *
     * @param Subsidiary $subsidiary
     * @return JsonResponse
     */
    public function delete(Subsidiary $subsidiary): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($subsidiary)
        );
    }

    /**
     * Eliminar una sucursal permanentemente (hard delete).
     *
     * @param Subsidiary $subsidiary
     * @return JsonResponse
     */
    public function destroy(Subsidiary $subsidiary): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($subsidiary)
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de la sucursal.
     *
     * @param Subsidiary $subsidiary
     * @return JsonResponse
     */
    public function toggleStatus(Subsidiary $subsidiary): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($subsidiary)
        );
    }

    /**
     * Generar una nueva contraseña para una sucursal.
     *
     * @param Subsidiary $subsidiary
     * @return JsonResponse
     */
    public function resetPassword(Subsidiary $subsidiary): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->resetPassword($subsidiary),
            ]
        );
    }
}

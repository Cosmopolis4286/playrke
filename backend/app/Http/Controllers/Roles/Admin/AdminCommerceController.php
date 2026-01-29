<?php

namespace App\Http\Controllers\Roles\Admin;

use App\DTOs\Roles\Admin\Commerce\AdminCommerceDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Admin\Commerce\AdminCommerceRequest;
use App\Models\Roles\Commerce;
use App\Services\Roles\Admin\Commerce\AdminCommerceService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de administración de Comercios.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class AdminCommerceController extends Controller
{
    /**
     * @param AdminCommerceService $service
     */
    public function __construct(
        protected readonly AdminCommerceService $service
    ) {
        $this->authorizeResource(Commerce::class, 'commerce');
    }

    /**
     * Crear un comercio.
     *
     * @param AdminCommerceRequest $request
     * @return JsonResponse
     */
    public function store(AdminCommerceRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->create(
                    $request->toDTO(AdminCommerceDTO::class)
                ),
            ]
        );
    }

    /**
     * Mostrar detalle de un comercio.
     *
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function show(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->show($commerce),
            ]
        );
    }

    /**
     * Actualizar un comercio.
     *
     * @param AdminCommerceRequest $request
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function update(
        AdminCommerceRequest $request,
        Commerce $commerce
    ): JsonResponse {
        return $this->handleServiceJson(
            fn() => $this->service->update(
                $commerce,
                $request->toDTO(AdminCommerceDTO::class)
            )
        );
    }

    /**
     * Eliminar un comercio (soft delete).
     *
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function delete(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($commerce)
        );
    }

    /**
     * Eliminar un comercio permanentemente (hard delete).
     *
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function destroy(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($commerce)
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del comercio.
     *
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function toggleStatus(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($commerce)
        );
    }

    /**
     * Generar una nueva contraseña para un comercio.
     *
     * @param Commerce $commerce
     * @return JsonResponse
     */
    public function resetPassword(Commerce $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->resetPassword($commerce),
            ]
        );
    }
}

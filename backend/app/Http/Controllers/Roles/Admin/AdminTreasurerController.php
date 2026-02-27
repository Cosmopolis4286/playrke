<?php

namespace App\Http\Controllers\Roles\Admin;

use App\Http\Controllers\Controller;

use App\DTOs\Roles\Admin\Treasurer\AdminTreasurerDTO;
use App\Http\Requests\Roles\Admin\Treasurer\AdminTreasurerRequest;
use App\Models\Roles\Treasurer;
use App\Services\Roles\Admin\Treasurer\AdminTreasurerService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de administración de Tesoreros.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class AdminTreasurerController extends Controller
{
    /**
     * @param AdminTreasurerService $service
     */
    public function __construct(
        protected readonly AdminTreasurerService $service
    ) {
        $this->authorizeResource(Treasurer::class, 'commerce');
    }

    /**
     * Crear un comercio.
     *
     * @param AdminTreasurerRequest $request
     * @return JsonResponse
     */
    public function store(AdminTreasurerRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->create(
                    $request->toDTO(AdminTreasurerDTO::class)
                ),
            ]
        );
    }

    /**
     * Listado de comercios
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
     * Mostrar detalle de un comercio.
     *
     * @param Treasurer $commerce
     * @return JsonResponse
     */
    public function show(Treasurer $commerce): JsonResponse
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
     * @param AdminTreasurerRequest $request
     * @param Treasurer $commerce
     * @return JsonResponse
     */
    public function update(AdminTreasurerRequest $request, Treasurer $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->update(
                $commerce,
                $request->toDTO(AdminTreasurerDTO::class)
            )
        );
    }

    /**
     * Eliminar un comercio (soft delete).
     *
     * @param Treasurer $commerce
     * @return JsonResponse
     */
    public function delete(Treasurer $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($commerce)
        );
    }

    /**
     * Eliminar un comercio permanentemente (hard delete).
     *
     * @param Treasurer $commerce
     * @return JsonResponse
     */
    public function destroy(Treasurer $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($commerce)
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del comercio.
     *
     * @param Treasurer $commerce
     * @return JsonResponse
     */
    public function toggleStatus(Treasurer $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($commerce)
        );
    }

    /**
     * Generar una nueva contraseña para un comercio.
     *
     * @param Treasurer $commerce
     * @return JsonResponse
     */
    public function resetPassword(Treasurer $commerce): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->resetPassword($commerce),
            ]
        );
    }
}

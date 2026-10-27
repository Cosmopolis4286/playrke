<?php

namespace App\Http\Controllers\Roles\Admin;

use App\DTOs\Roles\Admin\Treasurer\AdminTreasurerDTO;
use App\Http\Controllers\Controller;
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
    public function __construct(
        protected readonly AdminTreasurerService $service,
    ) {
        $this->authorizeResource(Treasurer::class, 'treasurer');
    }

    /**
     * Eliminar un tesorero (soft delete).
     */
    public function delete(Treasurer $treasurer) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->delete($treasurer),
        );
    }

    /**
     * Eliminar un tesorero permanentemente (hard delete).
     */
    public function destroy(Treasurer $treasurer) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->destroy($treasurer),
        );
    }

    /**
     * Listado de tesoreros
     */
    public function fetchList() : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => ['data' => $this->service->fetchList()],
        );
    }

    /**
     * Generar una nueva contraseña para un tesorero.
     */
    public function resetPassword(Treasurer $treasurer) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'password' => $this->service->resetPassword($treasurer),
            ],
        );
    }

    /**
     * Mostrar detalle de un tesorero.
     */
    public function show(Treasurer $treasurer) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'data' => $this->service->show($treasurer),
            ],
        );
    }

    /**
     * Crear un tesorero.
     */
    public function store(AdminTreasurerRequest $request) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'password' => $this->service->create(
                    $request->toDTO(AdminTreasurerDTO::class),
                ),
            ],
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del tesorero.
     */
    public function toggleStatus(Treasurer $treasurer) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->toggleStatus($treasurer),
        );
    }

    /**
     * Actualizar un tesorero.
     */
    public function update(AdminTreasurerRequest $request, Treasurer $treasurer) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->update(
                $treasurer,
                $request->toDTO(AdminTreasurerDTO::class),
            ),
        );
    }
}

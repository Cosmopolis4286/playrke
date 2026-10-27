<?php

namespace App\Http\Controllers\Roles\Admin;

use App\DTOs\Roles\Admin\Commerce\AdminCommerceDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Admin\Commerce\AdminCommerceRequest;
use App\Models\Roles\Commerce;
use App\Services\Roles\Admin\Commerce\AdminCommerceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    public function __construct(
        protected readonly AdminCommerceService $service,
    ) {
        $this->authorizeResource(Commerce::class, 'commerce');
    }

    /**
     * Eliminar un comercio (soft delete).
     */
    public function delete(Commerce $commerce) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->delete($commerce),
        );
    }

    /**
     * Eliminación lógica de varios comercios en una sola transacción.
     */
    public function deleteBulk(Request $request) : JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        return $this->handleServiceJson(
            fn () => $this->service->deleteBulk($request->input('ids')),
        );
    }

    /**
     * Eliminar un comercio permanentemente (hard delete).
     */
    public function destroy(Commerce $commerce) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->destroy($commerce),
        );
    }

    /**
     * Exporta a CSV todos los comercios que coincidan con los filtros
     * actuales (sin límite de paginación) — mismo patrón que
     * AdminPlayerController::export().
     */
    public function export(Request $request) : StreamedResponse
    {
        $filters = $request->only(['search', 'country_name', 'status']);

        return response()->streamDownload(function() use ($filters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nombre', 'País', 'Teléfono', 'Email', 'Comisión', 'Estado']);

            foreach ($this->service->exportForCsv($filters) as $commerce) {
                fputcsv($handle, [
                    $commerce->name,
                    $commerce->country?->name,
                    $commerce->phone,
                    $commerce->user?->email,
                    $commerce->commission,
                    $commerce->status,
                ]);
            }

            fclose($handle);
        }, 'comercios-' . now()->format('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Listado paginado de comercios (JSON). Server-side real: search,
     * country_name y status filtran contra TODO el catálogo, no solo la
     * página cargada en memoria — mismo patrón que AdminPlayerController::fetchList().
     */
    public function fetchList(Request $request) : JsonResponse
    {
        return $this->handleServiceJson(function() use ($request) {
            $filters = $request->only(['search', 'country_name', 'status']);
            $page = (int) $request->get('page', 1);

            $result = $this->service->fetchList($filters, $page);

            return [
                'data' => $result['rows'],
                'meta' => $result['pagination'],
            ];
        });
    }

    /**
     * Generar una nueva contraseña para un comercio.
     */
    public function resetPassword(Commerce $commerce) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'password' => $this->service->resetPassword($commerce),
            ],
        );
    }

    /**
     * Marcar el comercio como principal/vinculado a la app.
     */
    public function setAsDefault(Commerce $commerce) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->setAsDefault($commerce),
        );
    }

    /**
     * Mostrar detalle de un comercio.
     */
    public function show(Commerce $commerce) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'data' => $this->service->show($commerce),
            ],
        );
    }

    /**
     * Crear un comercio.
     */
    public function store(AdminCommerceRequest $request) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'password' => $this->service->create(
                    $request->toDTO(AdminCommerceDTO::class),
                ),
            ],
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del comercio.
     */
    public function toggleStatus(Commerce $commerce) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->toggleStatus($commerce),
        );
    }

    /**
     * Alternar estado de varios comercios en una sola transacción.
     */
    public function toggleStatusBulk(Request $request) : JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        return $this->handleServiceJson(
            fn () => $this->service->toggleStatusBulk($request->input('ids')),
        );
    }

    /**
     * Quitar la marca de comercio principal/vinculado a la app, sin
     * reasignarla a otro comercio.
     */
    public function unsetAsDefault(Commerce $commerce) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->unsetAsDefault($commerce),
        );
    }

    /**
     * Actualizar un comercio.
     */
    public function update(AdminCommerceRequest $request, Commerce $commerce) : JsonResponse
    {
        return $this->handleServiceJson(
            fn () => $this->service->update(
                $commerce,
                $request->toDTO(AdminCommerceDTO::class),
            ),
        );
    }
}

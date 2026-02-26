<?php

namespace App\Http\Controllers\Prizes;

use App\DTOs\Prizes\PrizeDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Prizes\PrizeRequest;
use App\Models\Prize;
use App\Services\Prizes\PrizeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración de Premios.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class PrizeController extends Controller
{
    public function __construct(
        protected readonly PrizeService $service
    ) {
        $this->authorizeResource(Prize::class, 'prize');
    }

    /**
     * Muestra el listado de premios usando Inertia.
     */
    public function index(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $response = $this->service->context();
                $response['title'] = 'Premios';

                return Inertia::render('Prizes/Index', $response);
            },
            'No se pudo cargar la lista de premios.'
        );
    }

    /**
     * Crear un premio.
     */
    public function store(PrizeRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->create(
                    $request->toDTO(PrizeDTO::class)
                ),
            ]
        );
    }

    /**
     * Listado plano de premios.
     */
    public function fetchList(): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->fetchList()]
        );
    }

    /**
     * Buscar premios individuales (no-kit) por nombre o código.
     */
    public function search(Request $request): JsonResponse
    {
        $query      = (string) $request->query('q', '');
        $excludeId  = $request->query('exclude');
        $rewardType = $request->query('reward_type');

        return $this->handleServiceJson(
            fn() => ['data' => $this->service->search($query, $excludeId, $rewardType)]
        );
    }

    /**
     * Mostrar detalle de un premio.
     */
    public function show(Prize $prize): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->show($prize),
            ]
        );
    }

    /**
     * Actualizar un premio.
     */
    public function update(PrizeRequest $request, Prize $prize): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request, $prize) {
            $affectedKits = $this->service->update(
                $prize,
                $request->toDTO(PrizeDTO::class)
            );

            return ['affected_kits' => $affectedKits];
        });
    }

    /**
     * Eliminar un premio (marca como INACTIVE).
     */
    public function delete(Prize $prize): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($prize)
        );
    }

    /**
     * Eliminar un premio permanentemente (hard delete).
     */
    public function destroy(Prize $prize): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($prize)
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del premio.
     */
    public function toggleStatus(Prize $prize): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($prize)
        );
    }
}

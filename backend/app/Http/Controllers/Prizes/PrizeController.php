<?php

namespace App\Http\Controllers\Prizes;

use App\DTOs\Prizes\PrizeDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Prize\PrizeRequest;
use App\Models\Prize;
use App\Services\Prizes\PrizeService;
use Illuminate\Http\JsonResponse;
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
    /**
     * @param PrizeService $service
     */
    public function __construct(
        protected readonly PrizeService $service
    ) {
        $this->authorizeResource(Prize::class, 'id');
    }

    /**
     * Muestra el listado de premios usando Inertia.
     *
     * @return Response
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
     *
     * @param PrizeRequest $request
     * @return JsonResponse
     */
    public function store(PrizeRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'password' => $this->service->create(
                    $request->toDTO(PrizeDTO::class)
                ),
            ]
        );
    }

    /**
     * Listado de premios
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
     * Mostrar detalle de un premio.
     *
     * @param Prize $prize
     * @return JsonResponse
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
     *
     * @param PrizeRequest $request
     * @param Prize $prize
     * @return JsonResponse
     */
    public function update(PrizeRequest $request, Prize $prize): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->update(
                $prize,
                $request->toDTO(PrizeDTO::class)
            )
        );
    }

    /**
     * Eliminar un premio (soft delete).
     *
     * @param Prize $prize
     * @return JsonResponse
     */
    public function delete(Prize $prize): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($prize)
        );
    }

    /**
     * Eliminar un premio permanentemente (hard delete).
     *
     * @param Prize $prize
     * @return JsonResponse
     */
    public function destroy(Prize $prize): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($prize)
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del premio.
     *
     * @param Prize $prize
     * @return JsonResponse
     */
    public function toggleStatus(Prize $prize): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($prize)
        );
    }
}

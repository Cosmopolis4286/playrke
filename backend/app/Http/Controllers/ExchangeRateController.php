<?php

namespace App\Http\Controllers;

use App\Factories\ExchangeRate\ExchangeRateFactory;
use App\Http\Requests\ExchangeRate\ExchangeRateRequest;
use App\Services\ExchangeRate\ExchangeRateService;
use App\Services\Token\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión de tasas de cambio.
 *
 * Orquesta peticiones HTTP y delega la lógica al dominio,
 * utilizando manejo centralizado de errores.
 */
class ExchangeRateController extends Controller
{
    public function __construct(
        private ExchangeRateFactory $factory,
        private ExchangeRateService $service,
        private TokenService $tokenService
    ) {}

    /**
     * Listado principal (Inertia).
     */
    public function index(Request $request): Response
    {
        return $this->handleServiceInertia(
            function () use ($request) {
                $token = $request->query('token');

                $this->tokenService->validateToken($token);

                return Inertia::render(
                    'ExchangeRate/Index',
                    [
                        ...$this->service->context($token),
                        'title' => 'Tasas de Cambio',
                    ]
                );
            },
            'No se pudo cargar la lista de tasas de cambios.'
        );
    }

    /**
     * API: listado filtrado por rol/token.
     */
    public function listFiltered(Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'data' => $this->service->getAll(
                    tap(
                        $request->query('token'),
                        fn ($token) => $this->tokenService->validateToken($token)
                    )
                ),
            ],
            'No se pudo cargar la lista de tasas de cambio.'
        );
    }

    /**
     * Crear una nueva tasa de cambio.
     */
    public function store(ExchangeRateRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'message' => 'Registro exitoso',
                'data'    => $this->service->create(
                    $this->factory->fromRequest($request->validated())
                ),
            ],
            'Hubo un error al registrar la tasa de cambio.'
        );
    }

    /**
     * Mostrar detalle de una tasa de cambio.
     */
    public function show(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            fn () => ['data' => $this->service->show($id)],
            'No se pudo cargar la información de la tasa de cambio.'
        );
    }

    /**
     * Actualizar una tasa de cambio.
     */
    public function update(ExchangeRateRequest $request, string $id): JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'message' => 'Actualización exitosa',
                'data'    => $this->service->update(
                    $this->factory->fromRequest(
                        $request->validated(),
                        $id
                    )
                ),
            ],
            'No se pudo actualizar la tasa de cambio.'
        );
    }

    /**
     * Eliminar una tasa de cambio (soft delete).
     */
    public function delete(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'message' => 'Tasa de cambio eliminada correctamente',
                'data'    => $this->service->delete($id),
            ],
            'No se pudo eliminar la tasa de cambio.'
        );
    }

    /**
     * Cambiar estado (activo/inactivo).
     */
    public function changeStatus(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'message' => 'Estado actualizado correctamente',
                'data'    => $this->service->toggleStatus($id),
            ],
            'No se pudo cambiar el estado de la tasa de cambio.'
        );
    }

    /**
     * Histórico de tasas (Inertia).
     */
    public function historical(Request $request): Response
    {
        return $this->handleServiceInertia(
            fn () => Inertia::render(
                'ExchangeRate/Index',
                $this->service->historicalContext(
                    $request->query('token')
                )
            ),
            'No se pudo cargar el histórico de tasas de cambio.'
        );
    }

    /**
     * Obtener tasas crudas desde proveedor externo.
     */
    public function getRawRates(Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'message' => 'Tasas obtenidas correctamente.',
                'data'    => $this->service->getRawRates(
                    tap(
                        $request->query('token'),
                        fn ($token) => $this->tokenService->validateToken($token)
                    )
                ),
            ],
            'No se pudieron obtener las tasas de cambio.'
        );
    }

    /**
     * Actualizar todas las tasas consultando proveedor externo.
     */
    public function updateAll(Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn () => [
                'data' => $this->service->updateRates(
                    tap(
                        $request->query('token'),
                        fn ($token) => $this->tokenService->validateToken($token)
                    )
                ),
            ],
            'No se pudo actualizar los valores de las tasas de cambio.'
        );
    }
}

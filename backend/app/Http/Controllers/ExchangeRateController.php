<?php

namespace App\Http\Controllers;

use App\Factories\ExchangeRate\ExchangeRateFactory;
use App\Http\Requests\ExchangeRate\ExchangeRateRequest;
use App\Http\Support\PublicTokenResolver;
use App\Services\ExchangeRate\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión de tasas de cambio.
 *
 * Orquesta peticiones HTTP y delega la lógica al dominio,
 * utilizando manejo centralizado de errores.
 *
 * - Rutas auth: no requieren token (usuario autenticado)
 * - Rutas public: el token es validado por PublicTokenMiddleware
 */
class ExchangeRateController extends Controller
{
    public function __construct(
        private ExchangeRateFactory $factory,
        private ExchangeRateService $service,
        private PublicTokenResolver $tokenResolver
    ) {}

    /**
     * Obtiene el token público desde el request (si existe).
     *
     * @param Request $request
     * @return string|null
     */
    private function resolveToken(Request $request): ?string
    {
        return $this->tokenResolver->resolve($request);
    }

    /**
     * Listado principal (Inertia).
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return $this->handleServiceInertia(
            fn() => Inertia::render(
                'ExchangeRate/Index',
                [
                    ...$this->service->context($this->resolveToken($request)),
                    'title' => 'Tasas de Cambio',
                ]
            ),
            'No se pudo cargar la lista de tasas de cambios.'
        );
    }

    /**
     * Listado filtrado por rol/token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchList(Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->getAll($this->resolveToken($request)),
            ],
            'No se pudo cargar la lista de tasas de cambio.'
        );
    }

    /**
     * Crear una nueva tasa de cambio.
     *
     * @param ExchangeRateRequest $request
     * @return JsonResponse
     */
    public function store(ExchangeRateRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
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
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->show($id)],
            'No se pudo cargar la información de la tasa de cambio.'
        );
    }

    /**
     * Actualizar una tasa de cambio.
     *
     * @param ExchangeRateRequest $request
     * @return JsonResponse
     */
    public function update(ExchangeRateRequest $request, string $id): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
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
     *
     * @param string $id
     * @return JsonResponse
     */
    public function delete(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'message' => 'Tasa de cambio eliminada correctamente',
                'data'    => $this->service->delete($id),
            ],
            'No se pudo eliminar la tasa de cambio.'
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE).
     *
     * @param string $id
     * @return JsonResponse
     */
    public function toggleStatus(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'message' => 'Estado actualizado correctamente',
                'data'    => $this->service->toggleStatus($id),
            ],
            'No se pudo cambiar el estado de la tasa de cambio.'
        );
    }

    /**
     * Histórico de tasas (Inertia).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function historical(Request $request): Response
    {
        return $this->handleServiceInertia(
            fn() => Inertia::render(
                'ExchangeRate/Index',
                $this->service->historicalContext($this->resolveToken($request))
            ),
            'No se pudo cargar el histórico de tasas de cambio.'
        );
    }

    /**
     * Obtener tasas crudas desde proveedor externo.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRawRates(Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'message' => 'Tasas obtenidas correctamente.',
                'data'    => $this->service->getRawRates($this->resolveToken($request)),
            ],
            'No se pudieron obtener las tasas de cambio.'
        );
    }

    /**
     * Actualizar todas las tasas con los valores confirmados por el usuario.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAll(Request $request): JsonResponse
    {
        $rates = $request->input('rates', []);

        return $this->handleServiceJson(
            fn() => [
                'message' => 'Tasas de cambio actualizadas correctamente.',
                'data' => $this->service->updateRates($rates, $this->resolveToken($request)),
            ],
            'No se pudo actualizar los valores de las tasas de cambio.'
        );
    }
}

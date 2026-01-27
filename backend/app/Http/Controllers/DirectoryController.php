<?php

namespace App\Http\Controllers;

use App\Factories\ExchangeRate\ExchangeRateFactory;
use App\Http\Requests\ExchangeRate\ExchangeRateRequest;
use App\Services\Directory\DirectoryService;
use App\Services\Token\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión de directorios.
 *
 * Recibe las peticiones HTTP y delega en el DirectoryService.
 * Maneja respuestas Inertia y JSON, con manejo centralizado de errores.
 */
class DirectoryController extends Controller
{
    public function __construct(
        private ExchangeRateFactory $factory,
        private DirectoryService $service,
        private TokenService $tokenService
    ) {}

    /**
     * Muestra el directorio de acuerdo al codigo ISO usando Inertia.
     *
     * @param string $countryCode Codigo ISO del país (opcional)
     * @return Response
     */
    public function index(?string $countryCode = null): Response
    {
        return $this->handleServiceInertia(
            function () use ($countryCode) {

                $response = $this->service->context($countryCode);

                return Inertia::render('Directory/Index', $response);
            },
            'No se pudo cargar la lista de tasas de cambios.'
        );
    }

    /**
     * Endpoint API para obtener tasas filtradas según rol y token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listFiltered(Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($request) {
                $token = $request->query('token', null);

                $this->tokenService->validateToken($token);

                $data = $this->service->getAll($token);

                return ['data' => $data];
            },
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
            function () use ($request) {
                $dto = $this->factory->fromRequest($request->validated());

                $response = $this->service->create($dto);

                if ($response === true) {
                    return ['message' => '✅ Registro exitoso'];
                }

                // Retorna false para que el helper considere error
                return false;
            },
            'Hubo un error al registrar la tasa de cambio.'
        );
    }

    /**
     * Mostrar detalle de una tasa de cambio por su ID.
     *
     * @param string $id Identificador de la tasa de cambio.
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
     * Actualizar una tasa de cambio existente.
     *
     * @param ExchangeRateRequest $request
     * @param string $id Identificador de la tasa de cambio.
     * @return JsonResponse
     */
    public function update(ExchangeRateRequest $request, string $id): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($request, $id) {
                $dto = $this->factory->fromRequest($request->validated(), $id);

                $response = $this->service->update($dto);

                if ($response === true) {
                    return ['message' => '✅ Actualización exitosa'];
                }

                return false;
            },
            'No se pudo actualizar la tasa de cambio.'
        );
    }

    /**
     * Eliminar una tasa de cambio (soft delete).
     *
     * @param string $id Identificador de la tasa de cambio.
     * @return JsonResponse
     */
    public function delete(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($id) {
                $response = $this->service->delete($id);

                if ($response === true) {
                    return ['message' => '✅ Tasa de cambio eliminada correctamente'];
                }

                return false;
            },
            'No se pudo eliminar la tasa de cambio.'
        );
    }

    /**
     * Cambiar el estado (activo/inactivo) de una tasa de cambio.
     *
     * @param string $id Identificador de la tasa de cambio.
     * @return JsonResponse
     */
    public function changeStatus(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($id) {
                $this->service->toggleStatus($id);

                return ['message' => '✅ Estado actualizado correctamente'];
            },
            'No se pudo cambiar el estado de la tasa de cambio.'
        );
    }

    /**
     * Mostrar histórico de tasas de cambio con Inertia.
     *
     * @param Request $request
     * @return Response
     */
    public function historical(Request $request): Response
    {
        return $this->handleServiceInertia(
            fn() => Inertia::render(
                'ExchangeRate/Index',
                $this->service->historicalContext($request->query('token', null))
            ),
            'No se pudo cargar el histórico de tasas de cambio.'
        );
    }

    /**
     * Retorna las tasas crudas obtenidas de la API externa para mostrar en confirmación.
     *
     * @return JsonResponse
     */
    public function getRawRates(Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($request) {
                $token = $request->query('token');

                $this->tokenService->validateToken($token);

                $rates = $this->service->getRawRates($token);

                if (!$rates) return false;

                return [
                    'message' => 'Tasas obtenidas correctamente.',
                    'data'    => $rates,
                ];
            },
            'No se pudieron obtener las tasas de cambio.',
            500
        );
    }

    /**
     * Actualizar todas las tasas consultando proveedor externo.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAll(Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->updateRates(
                    $request->query('token')
                ),
            ],
            'No se pudo actualizar los valores de las tasas de cambio.'
        );
    }
}

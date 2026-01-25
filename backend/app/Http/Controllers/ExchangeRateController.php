<?php

namespace App\Http\Controllers;

use App\Factories\ExchangeRate\ExchangeRateFactory;
use App\Http\Requests\ExchangeRate\ExchangeRateRequest;
use App\Services\ExchangeRate\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión de tasas de cambio.
 */
class ExchangeRateController extends Controller
{
    public function __construct(
        protected ExchangeRateFactory $factory,
        protected ExchangeRateService $service
    ) {}

    /**
     * Muestra el listado de tasas de cambio utilizando Inertia.
     *
     * Este endpoint renderiza una vista Inertia y maneja excepciones con
     * manejo centralizado mediante `handleServiceInertia`.
     *
     * @param Request $request
     *
     * @return \Inertia\Response
     */
    public function index(Request $request): Response
    {
        return $this->handleServiceInertia(
            function () use ($request) {
                $response = $this->service->context($request->query('token', null));

                return Inertia::render('ExchangeRate/Index', $response);
            },
            'No se pudo cargar la lista de tasas de cambios.'
        );
    }

    /**
     * Endpoint API para obtener tasas según rol y token (listado filtrado).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listFiltered(Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($request) {
                $token = $request->query('token', null);

                $data = $this->service->getAll($token);

                return ['data' => $data];
            },
            'No se pudo cargar la lista de tasas de cambio.'
        );
    }

    /**
     * Crear/registrar una tasa de cambio.
     *
     * Endpoint JSON para uso vía AJAX / API.
     *
     * @param ExchangeRateRequest $request
     *
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

                // Si es false, no retorna nada para que se considere error
                return false;
            },
            'Hubo un error al registrar la tasa de cambio.'
        );
    }

    /**
     * Mostrar detalle de una tasa de cambio por su ID.
     *
     * @param string $id Identificador de la tasa de cambio.
     * @return \Illuminate\Http\JsonResponse
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
     * @param mixed $id
     * @return JsonResponse
     */
    public function update(ExchangeRateRequest $request, $id): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($request, $id) {
                $dto = $this->factory->fromRequest($request->validated(), $id);

                $response = $this->service->update($dto);

                if ($response === true) {
                    return ['message' => '✅ Actualización exitosa'];
                }

                // Si es false, no retorna nada para que se considere error
                return false;
            },
            'No se pudo actualizar la tasa de cambio.'
        );
    }

    /**
     * Eliminar tasa de cambio (soft delete)
     *
     * @param string $id
     * @return JsonResponse
     */
    public function delete(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($id) {
                $response = $this->service->delete($id);

                if ($response === true) {
                    return ['message' => '✅ Tasa de cambio eliminado correctamente'];
                }

                // Si es false, no retorna nada para que se considere error
                return false;
            },
            'No se pudo eliminar la ta de cambio.'
        );
    }

    /**
     * Cambiar el estado de una tasa de cambio.
     *
     * @param string $id
     *
     * @return JsonResponse
     */
    public function changeStatus(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($id) {
                $this->service->toggleStatus($id);
                return ['message' => '✅ Estado actualizado correctamente',];
            },
            'No se pudo cambiar el estado de la tasa de cambio.'
        );
    }

    /**
     * Muestra el historico de tasas de cambio utilizando Inertia.
     *
     * Este endpoint renderiza una vista Inertia y no devuelve una respuesta JSON.
     * El manejo de excepciones y redirecciones se realiza mediante `handleService`.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function historical(Request $request): Response
    {
        return $this->handleServiceInertia(
            fn() => Inertia::render(
                'ExchangeRate/Index',
                $this->service->historicalContext($request->query('token', null))
            ),
            'No se pudo cargar el historico de tasas de cambios.'
        );
    }

    /**
     * Actualiza todas las tasas de cambio consultando un proveedor externo.
     *
     * El token se recibe como query param (?token=...).
     * La respuesta se maneja mediante `handleServiceJson`.
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

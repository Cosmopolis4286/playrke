<?php

namespace App\Http\Controllers;

use App\Factories\ExchangeRate\ExchangeRateFactory;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Http\Requests\ExchangeRate\ExchangeRateRequest;
use App\Services\ExchangeRate\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
            fn() => Inertia::render(
                'ExchangeRate/Index',
                $this->service->context($request)
            ),
            'No se pudo cargar la lista de tasas de cambios.'
        );
    }

    /**
     * Crear una nueva tasa de cambio.
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

                $this->service->create($dto);

                return ['message' => '✅ Registro exitoso'];
            },
            'Hubo un error al registrar la tasa de cambio.'
        );
    }

    /**
     * Mostrar detalle de tasa de cambio
     */
    public function show(string $id): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->getByExchangeRate($id)],
            'No se pudo cargar la información de la tasa de cambio.'
        );
    }

    /**
     * Actualizar una tasa de cambio existente.
     *
     * @param ExchangeRateRequest $request
     *
     * @return RedirectResponse
     */
    public function update(ExchangeRateRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($request) {
                $dto = $this->factory->fromRequest($request->validated());

                $this->service->update($dto);

                return ['message' => '✅ Actualización exitosa'];
            },
            'No se pudo actualizar la tasa de cambio.'
        );
    }

    /**
     * Eliminar una tasa de cambio.
     *
     * @param string $payload
     *
     * @return RedirectResponse
     */
    public function delete(string $payload): RedirectResponse
    {
        return $this->handleService(
            function () use ($payload) {
                $this->service->delete(
                    ExchangeRateDTO::fromPayload($payload)
                );

                return back()->with(
                    'message',
                    'La tasa de cambio fue eliminada'
                );
            },
            'No se pudo eliminar la tasa de cambio.'
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
                $this->service->changeExchangeRateStatus($id);
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
        return $this->handleService(
            function () use ($request) {
                return Inertia::render(
                    'ExchangeRate/Index',
                    $this->service->historicalContext($request)
                );
            },
            'No se pudo cargar la lista de tasas de cambios.'
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

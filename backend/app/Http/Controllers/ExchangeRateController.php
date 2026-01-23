<?php

namespace App\Http\Controllers;

use App\Factories\ExchangeRate\ExchangeRateFactory;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Http\Requests\ExchangeRate\ExchangeRateRequest;
use App\Models\ExchangeRate;
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
     * Este endpoint renderiza una vista Inertia y no devuelve una respuesta JSON.
     * El manejo de excepciones y redirecciones se realiza mediante `handleService`.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        return $this->handleService(
            function () use ($request) {
                return Inertia::render(
                    'ExchangeRate/Index',
                    $this->service->context($request)
                );
            },
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
     * @param string $payload
     *
     * @return RedirectResponse
     */
    public function update(
        ExchangeRateRequest $request,
        string $payload
    ): RedirectResponse {
        return $this->handleService(
            function () use ($request, $payload) {
                $this->service->update(
                    ExchangeRateDTO::fromPayload($payload, $request)
                );

                return back()->with(
                    'message',
                    'La información fue actualizada con éxito'
                );
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
     * @param ExchangeRate $exchangeRate
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function changeStatus(ExchangeRate $exchangeRate): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($exchangeRate) {
                $this->service->changeStatus($exchangeRate);
                return ['message' => '✅ Estado actualizado correctamente',];
            },
            'No se pudo cambiar el estado de la tasa de cambio.'
        );
    }

    /**
     * Mostrar el histórico de tasas de cambio.
     *
     * Renderiza una vista Blade tradicional.
     *
     * @param string $type
     * @param string $creatorId
     * @param string $currency
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function historical(
        string $type,
        string $creatorId,
        string $currency
    ) {
        return $this->handleService(
            fn() => view(
                'modules.all.exchange-rate.historical',
                $this->service->historical($type, $creatorId, $currency)
            ),
            'No se pudo cargar el histórico de tasas.'
        );
    }

    /**
     * Actualiza todos los valores ('amount') de las tasas de cambio, consultando una API publica.
     *
     * Este endpoint renderiza una vista Inertia y devuelve una respuesta JSON.
     * El manejo de excepciones y redirecciones se realiza mediante `handleServiceJson`.
     *
     * @param string $token ID del token de usuario por api publica (opcinal)
     *
     * @return JsonResponse
     */
    public function updateAll(?string $token = null): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->updateRates($token)],
            'No se pudo actualizar los valores de las tasas de cambio.'
        );
    }
}

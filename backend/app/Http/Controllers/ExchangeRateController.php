<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ExchangeRate\ExchangeRateService;
use App\Traits\Users\UserTrait;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Http\Requests\StoreExchangeRateRequest;
use App\Http\Requests\UpdateExchangeRateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión de tasas de cambio
 */
class ExchangeRateController extends Controller
{
    use UserTrait;

    public function __construct(
        protected ExchangeRateService $service
    ) {}

    /**
     * Muestra el listado de tasas de cambio usando Inertia.
     *
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function index(Request $request): Response|RedirectResponse
    {
        return $this->handleService(function () use ($request) {
            $context = $this->service->context($request);

            dd($context);

            return Inertia::render('ExchangeRate/Index', $context->toView());
        }, 'No se pudo cargar la lista de tasas de cambios.');
    }

    /**
     * Crear tasa de cambio
     */
    public function store(StoreExchangeRateRequest $request): RedirectResponse
    {
        $this->service->create(
            ExchangeRateDTO::fromRequest($request)
        );

        return back()->with('message', 'La tasa de cambio ha sido cargada con éxito');
    }

    /**
     * Actualizar tasa de cambio
     */
    public function update(UpdateExchangeRateRequest $request, string $payload): RedirectResponse
    {
        $this->service->update(
            ExchangeRateDTO::fromPayload($payload, $request)
        );

        return back()->with('message', 'La información fue actualizada con éxito');
    }

    /**
     * Eliminar tasa de cambio
     */
    public function delete(string $payload): RedirectResponse
    {
        $this->service->delete(
            ExchangeRateDTO::fromPayload($payload)
        );

        return back()->with('message', 'La tasa de cambio fue eliminada');
    }

    /**
     * Cambiar estado ACTIVE / INACTIVE
     */
    public function changeStatus(string $payload): RedirectResponse
    {
        $this->service->toggleStatus(
            ExchangeRateDTO::fromPayload($payload)
        );

        return back()->with('message', 'Status actualizado');
    }

    /**
     * Histórico de tasas
     */
    public function historical(
        string $type,
        string $creatorId,
        string $currency
    ): View {
        return view(
            'modules.all.exchange-rate.historical',
            $this->service->historical($type, $creatorId, $currency)
        );
    }
}

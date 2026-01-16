<?php

namespace App\Http\Controllers;

// Base Controller
use App\Http\Controllers\Controller;
use App\Services\ExchangeRate\ExchangeRateService;
use App\Traits\Users\UserTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExchangeRateController extends Controller
{
    use UserTrait;

    public function __construct(
        protected ExchangeRateService $service
    ) {}

    /**
     * Listado de tasas de cambio
     */
    public function index(Request $request): View|RedirectResponse
    {
        return $this->handleService(function () use ($request) {
            $context = $this->service->resolveContext($request);
            return view('modules.all.exchange-rate.index', $context->toView());
        }, 'No se pudo cargar la lista de tasas de cambios.');
    }

    public function store(StoreExchangeRateRequest $request)
    {
        $this->service->create(ExchangeRateData::fromRequest($request));

        return back()->with('message', 'La tasa de cambio ha sido cargada con éxito');
    }

    public function update(UpdateExchangeRateRequest $request, string $payload)
    {
        $this->service->update(
            ExchangeRateData::fromPayload($payload, $request)
        );

        return back()->with('message', 'La información fue actualizada con éxito');
    }

    public function delete(string $payload)
    {
        $this->service->delete($payload);

        return back()->with('message', 'La tasa de cambio fue eliminada');
    }

    public function changeStatus(string $payload)
    {
        $this->service->toggleStatus($payload);

        return back()->with('message', 'Status actualizado');
    }

    public function historical(string $type, string $creatorId, string $currency)
    {
        return view(
            'modules.all.exchange-rate.historical',
            $this->service->historical($type, $creatorId, $currency)
        );
    }
}

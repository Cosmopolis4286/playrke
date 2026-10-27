<?php

namespace App\Http\Controllers\ExchangeRate;

use App\Http\Controllers\Controller;
use App\Services\ExchangeRate\NetworkExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tasa de cambio vigente para la red del usuario — ver NetworkExchangeRateService.
 * Lo consume ExchangeRateWidget.vue (tableros y página de tasas).
 */
final class NetworkExchangeRateController extends Controller
{
    public function __construct(
        private readonly NetworkExchangeRateService $service,
    ) {
    }

    public function index(Request $request) : JsonResponse
    {
        $this->authorize('exchange.viewNetwork');

        return $this->handleServiceJson(fn () => ['data' => $this->service->forUser($request->user())]);
    }
}

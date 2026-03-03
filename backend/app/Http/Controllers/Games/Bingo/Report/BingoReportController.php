<?php

namespace App\Http\Controllers\Games\Bingo\Report;

use App\DTOs\Games\Bingo\Card\BingoCardGenerateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Card\BingoCardGenerateRequest;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Services\Games\Bingo\Card\BingoCardGenerateService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración del juego de Bingo.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class BingoReportController extends Controller
{
    /**
     * @param BingoRoomService $service
     */
    public function __construct(
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly BingoCardGenerateService $generateService,
    ) {
        $this->authorizeResource(BingoRoom::class, 'room');
    }

    /**
     * Vista para generar cartones de bingo.
     *
     * @return Response
     */
    public function viewGenerateCard(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $userId = $this->userContext->resolveUserId();
                $context = $this->generateService->viewContext($userId);

                return Inertia::render('Games/Bingo/Card/Generate', $context);
            },
            'No se pudo cargar la vista para generar cartones de bingo.'
        );
    }

    /**
     * Generar cartones de bingo para un jugador.
     *
     * @param BingoCardGenerateRequest $request
     * @return JsonResponse
     */
    public function generateCardStore(BingoCardGenerateRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->generateService->generate(
                $request->toDTO(BingoCardGenerateDTO::class)
            )
        );
    }

    public function availableCountries($roomId)
    {
        $seller = $this->seller(['id', 'commerce_id']);
        $creatorUser = Commerce::getValueById($seller->commerce, 'user');

        // Obtenemos las monedas disponibles para el vendedor y el comercio
        $sellerCurrencies = SellerCurrency::getAvailableCurrencies($seller->id);
        $commerceCurrencies = CommerceExchangeRate::getCurrenciesByCommerce($seller->commerce, 'ACTIVE');

        // Obtenemos el campo allowed_currencies (json o null) y lo forzamos a array
        $allowedCurrenciesRoom = BingoCreator::where('creator_user_id', $creatorUser)
            ->where('room', $roomId)->value('allowed_currencies');

        $allowedCurrenciesRoom = is_array($allowedCurrenciesRoom) ? $allowedCurrenciesRoom : [];

        $currenciesAvailable = array_values(array_intersect(
            array_map('strval', $sellerCurrencies),
            array_map('strval', $commerceCurrencies),
            array_map('strval', $allowedCurrenciesRoom)
        ));

        // Eliminamos '1' si existe
        $USDT = ($key = array_search('1', $currenciesAvailable, true)) !== false;
        if ($USDT) {
            unset($currenciesAvailable[$key]);
            $currenciesAvailable = array_values($currenciesAvailable);
        }

        $countriesAvailable = Currency::getCountriesByCurrencyInArray($currenciesAvailable);

        $countries = Country::getCountries($countriesAvailable, ['id', 'name']);

        return ['countries' => $countries, 'USDT' => $USDT];
    }
}

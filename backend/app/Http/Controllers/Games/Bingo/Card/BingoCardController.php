<?php

namespace App\Http\Controllers\Games\Bingo\Card;

use App\DTOs\Games\Bingo\Card\Generate\BingoCardGenerateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Card\BingoCardGenerateRequest;
use App\Models\Games\Bingo\Room\BingoRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración de Cartones de Bingo.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class BingoCardController extends Controller
{
    /**
     * @param BingoRoomService $service
     */
    public function __construct(
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly \App\Services\Games\Bingo\Card\Generate\BingoCardGenerateService $generateService,
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
                $context = $this->generateService->viewContext($userId)->toView();

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

    /**
     * Obtener lista/detalles de salas de bingo filtradas.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchRoom(Request $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->generateService->fetchRoom(
                    $request->all() ?? []
                ),
            ]
        );
    }

    /**
     * Obtener lista de paises disponibles para el vendedor.
     * - En base a las monedas permitidas de su manager_user_id, si aplica
     * - En base a las monedas permitidas de la sala de bingo
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function availableCountries(BingoRoom $room): JsonResponse
    {
        $userId = $this->userContext->resolveUserId();

        return $this->handleServiceJson(
            fn() => $this->generateService->getAvailableCountries($room->id, $userId)
        );
    }

    /**
     * Obtener el precio del cartón para una sala y país específicos.
     *
     * El precio se calcula aplicando:
     * - Precio personalizado en bingo_card_prices (si existe para la sala y moneda del país).
     * - En su defecto, precio base de la sala (card_price_usd) × tasa de cambio del commerce.
     *
     * @param BingoRoom $room    Sala de bingo (model binding por {room}).
     * @param Request   $request Parámetros de la petición; debe incluir `country_id` (string Snowflake).
     * @return JsonResponse      Respuesta con card_price, currency_symbol, exchange_rate, is_custom_price.
     */
    public function cardPrice(BingoRoom $room, Request $request): JsonResponse
    {
        $userId    = $this->userContext->resolveUserId();
        $countryId = (string) $request->query('country_id');

        return $this->handleServiceJson(
            fn() => $this->generateService->getCardPrice($room->id, $userId, $countryId)
        );
    }
}

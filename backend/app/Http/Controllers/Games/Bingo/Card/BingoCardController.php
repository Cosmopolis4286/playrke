<?php

namespace App\Http\Controllers\Games\Bingo\Card;

use App\Domains\Games\Bingo\DTOs\Card\Generate\BingoCardGenerateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Card\BingoCardGenerateRequest;
use App\Http\Requests\Games\Bingo\Room\BingoRoomPlayerBuyRequest;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
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
     * El `{room}` en la URL usa route model binding (HasHashedRouteKey),
     * por lo que el hash se decodifica automáticamente antes de llegar aquí.
     *
     * @param BingoRoom                $room    Sala decodificada por route model binding.
     * @param BingoCardGenerateRequest $request Body con num_ctns, country_id y player.
     * @return JsonResponse
     */
    public function generateCardStore(BingoRoom $room, BingoCardGenerateRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->generateService->generate(
                BingoCardGenerateDTO::fromArray($request->validated())
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

    /**
     * Obtener el precio del cartón para un jugador usando su billetera configurada.
     *
     * Para usuarios con rol 'player', obtiene el precio basado en:
     * - Moneda configurada en la billetera del jugador
     * - Tasa de cambio correspondiente
     * - Precio personalizado si existe para la sala y moneda
     *
     * @param BingoRoom $room Sala de bingo (model binding por {room}).
     * @return JsonResponse  Respuesta con card_price, currency_symbol, exchange_rate, is_custom_price.
     */
    public function playerCardPrice(BingoRoom $room): JsonResponse
    {
        $userId = $this->userContext->resolveUserId();

        return $this->handleServiceJson(
            fn() => $this->generateService->getPlayerCardPrice($room->id, $userId)
        );
    }

    /**
     * Comprar cartones para un jugador desde la vista de salas de juego.
     *
     * @param BingoRoom                $room    Sala de bingo (model binding por {room}).
     * @param BingoRoomPlayerBuyRequest $request Datos de la compra.
     * @return JsonResponse
     */
    public function purchaseCards(BingoRoom $room, BingoRoomPlayerBuyRequest $request): JsonResponse
    {
        // Validate room state
        if ($room->status_code !== 'ACTIVE') {
            return response()->json([
                'message' => 'No se pueden comprar cartones en este momento. La sala debe estar abierta.'
            ], 400);
        }

        if ($room->is_closed) {
            return response()->json([
                'message' => 'La sala está cerrada para nuevas compras.'
            ], 400);
        }

        return $this->handleServiceJson(
            function () use ($room, $request) {
                $result = $this->generateService->playerBuyFromGameRooms(
                    $room->id,
                    (int) $request->validated()['num_ctns']
                );

                return [
                    'message' => 'Cartones comprados exitosamente',
                    'data' => $result,
                    'user_cards_count' => count($result['cards'] ?? [])
                ];
            },
            'Error al procesar la compra de cartones.'
        );
    }

    /**
     * Obtener los cartones del jugador actual en una sala específica.
     *
     * @param BingoRoom $room Sala de bingo (model binding por {room}).
     * @return JsonResponse
     */
    public function getPlayerCards(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($room) {
                $userId = $this->userContext->resolveUserId();

                // This would need to be implemented in the service
                // For now, return empty array
                return [
                    'cards' => [],
                    'count' => 0
                ];
            },
            'Error al obtener los cartones del jugador.'
        );
    }
}

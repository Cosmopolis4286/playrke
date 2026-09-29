<?php

namespace App\Http\Controllers\Games\Bingo\Card;

use App\Domains\Games\Bingo\DTOs\Card\Generate\BingoCardGenerateDTO;
use App\Domains\Games\Bingo\Services\Card\Generate\BingoSellerPosService;
use App\Services\Auth\PermissionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Card\BingoCardGenerateRequest;
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
        private readonly \App\Domains\Games\Bingo\Services\Card\Generate\BingoCardGenerateService $generateService,
        private readonly BingoSellerPosService $posService,
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
     * @param BingoCardGenerateRequest $request Body con card_count, country_id y player.
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
     * Punto de venta: todas las salas vendibles de una fecha en una sola
     * llamada (el cliente filtra por tipo de sala / conexión / bote).
     */
    public function rooms(Request $request): JsonResponse
    {
        $this->ensureSeller();
        $validated = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        return $this->handleServiceJson(
            fn () => ['rooms' => $this->generateService->listSellableRooms($validated['date'])]
        );
    }

    /**
     * Punto de venta: compradores del vendedor para autocompletar.
     */
    public function frequentBuyers(Request $request): JsonResponse
    {
        $this->ensureSeller();
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:60']]);

        return $this->handleServiceJson(fn () => [
            'buyers' => $this->posService->frequentBuyers(
                (string) $this->userContext->resolveUserId(),
                $validated['q'] ?? null,
            ),
        ]);
    }

    /**
     * Punto de venta: ventas del día del vendedor y sus totales.
     */
    public function todaySales(): JsonResponse
    {
        $this->ensureSeller();

        return $this->handleServiceJson(
            fn () => $this->posService->todaySales((string) $this->userContext->resolveUserId())
        );
    }

    /**
     * Los endpoints del punto de venta exponen datos personales de
     * compradores: solo el vendedor (mismo criterio que
     * BingoCardGenerateRequest::authorize()).
     */
    private function ensureSeller(): void
    {
        abort_unless(PermissionService::checkPrefix(['seller']), 403, 'Solo los vendedores pueden usar el punto de venta.');
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
}

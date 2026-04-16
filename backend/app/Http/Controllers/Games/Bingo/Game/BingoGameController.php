<?php

namespace App\Http\Controllers\Games\Bingo\Game;

use App\Http\Controllers\Controller;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
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
final class BingoGameController extends Controller
{
    /**
     * @param BingoRoomService $service
     */
    public function __construct(
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly \App\Services\Games\Bingo\Game\BingoGameService $service,
        private readonly \App\Services\Games\Bingo\Game\BingoDrawService $drawService,
    ) {
        $this->authorizeResource(BingoRoom::class, 'room');
    }
    
    /**
     * Vista del juego de bingo (jugador en sala).
     *
     * @param BingoRoom $room
     * @return Response
     */
    public function inGame(BingoRoom $room): Response
    {
        return $this->handleServiceInertia(
            function () use ($room) {
                $userId = $this->userContext->resolveUserId();
                $response = $this->service->getInGameData($room, $userId);
                $response['isLocal'] = app()->environment('local');

                return Inertia::render('Games/Bingo/Game/Index', $response);
            },
            'No se pudo cargar la sala de juego.'
        );
    }

    /**
     * Obtener cartones del usuario en una sala (API).
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function getUserCards(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($room) {
                $userId = $this->userContext->resolveUserId();
                $cartons = $this->service->getUserCartons($room->id, $userId);

                return [
                    'success' => true,
                    'data' => $cartons,
                    'message' => 'Cartones obtenidos exitosamente'
                ];
            },
            'No se pudieron obtener los cartones del usuario.'
        );
    }

    /**
     * [DEV] Resetea unix_date de la sala a now+3min para pruebas locales.
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function devResetStartTime(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->devResetStartTime($room)
        );
    }

    /**
     * [DEV] Inicia el sorteo de la sala (genera fichas, persiste y broadcast).
     * Solo disponible en entorno local.
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function startDraw(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->drawService->startDraw($room)
        );
    }

    /**
     * Vista legacy del juego de bingo (compatibilidad).
     *
     * @param string $player
     * @param string $room
     * @return Response
     */
    public function legacyInGame(string $player, string $room = ''): Response
    {
        return $this->handleServiceInertia(
            function () use ($player, $room) {
                $response = $this->service->getLegacyInGameData($player, $room);

                return Inertia::render('Games/Bingo/Game/Index', $response);
            },
            'No se pudo cargar la sala de juego.'
        );
    }
}

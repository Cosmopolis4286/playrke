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
        private readonly \App\Services\Games\Bingo\Game\BingoWinnerDetectionService $winnerDetection,
    ) {
        $this->authorizeResource(BingoRoom::class, 'room');
    }

    /**
     * Vista del lobby de bingo (pantalla previa al juego).
     *
     * @param BingoRoom $room
     * @return Response
     */
    public function lobby(BingoRoom $room): Response
    {
        return $this->handleServiceInertia(
            function () use ($room) {
                $userId = $this->userContext->resolveUserId();
                $userCards = $room->cards()
                    ->whereHas('roomUser', fn($q) => $q->where('user_id', $userId))
                    ->where('status', 'active')
                    ->count();

                return Inertia::render('Games/Bingo/Lobby/Index', [
                    'room' => $room,
                    'roomRouteKey' => $room->getRouteKey(),
                    'userCards' => $userCards,
                ]);
            },
            'No se pudo cargar el lobby.'
        );
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

                \Log::info('[BingoGameController] inGame loaded', [
                    'room_id' => $room->id,
                    'user_id' => $userId,
                    'auth_check' => auth()->check(),
                    'auth_id' => auth()->id(),
                ]);

                $response = $this->service->getInGameData($room, $userId);
                $response['isLocal'] = app()->environment('local');

                \Log::info('[BingoGameController] inGame response', [
                    'cartons_count' => count($response['cartons'] ?? []),
                    'cartons_data' => $response['cartons'] ?? [],
                ]);

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
            function () use ($room) {
                $roomId = (int) $room->id;

                dispatch(function () use ($roomId) {
                    $room = BingoRoom::find($roomId);

                    if (!$room) {
                        \Log::warning('[BingoGameController] No se pudo iniciar sorteo: sala no encontrada', [
                            'room_id' => $roomId,
                        ]);

                        return;
                    }

                    app(\App\Services\Games\Bingo\Game\BingoDrawService::class)->startDraw($room);
                })->afterResponse();

                return [
                    'success' => true,
                    'room_id' => $roomId,
                    'message' => 'Sorteo iniciado. Las fichas se emitirán por WebSocket.',
                ];
            }
        );
    }

    /**
     * Verifica si el usuario tiene bingo en alguno de sus cartones.
     * Si hay bingo, declara al ganador y dispara evento WebSocket.
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function checkBingo(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            function () use ($room) {
                $userId = $this->userContext->resolveUserId();
                $winner = $this->winnerDetection->checkWinnerForUser($room, $userId);

                if ($winner) {
                    $this->winnerDetection->declareWinner($room, $winner);
                    return [
                        'success' => true,
                        'hasBingo' => true,
                        'winner' => $winner,
                        'message' => '¡BINGO! Has completado tu cartón.',
                    ];
                }

                return [
                    'success' => true,
                    'hasBingo' => false,
                    'message' => 'No tienes bingo aún. Sigue atento.',
                ];
            },
            'Error al verificar bingo.'
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

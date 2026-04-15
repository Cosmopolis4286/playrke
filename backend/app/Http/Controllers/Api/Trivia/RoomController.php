<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Trivia;

use App\Domains\Games\Trivia\Actions\JoinRoomAction;
use App\Domains\Games\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Games\Trivia\DTOs\RoomDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trivia\CreateRoomRequest;
use App\Http\Requests\Trivia\JoinRoomRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller API: Gestión de Salas.
 * 
 * Sin lógica de negocio - solo orquesta llamadas a Actions/Services.
 */
class RoomController extends Controller
{
    public function __construct(
        private readonly RoomServiceInterface $roomService,
        private readonly JoinRoomAction $joinRoomAction
    ) {}

    /**
     * Listar salas disponibles.
     */
    public function index(Request $request): JsonResponse
    {
        // Listar salas en espera
        // En producción, usar paginación y filtros

        return response()->json([
            'message' => 'Listado de salas',
            'data' => [], // Implementar según necesidad
        ]);
    }

    /**
     * Crear nueva sala.
     */
    public function store(CreateRoomRequest $request): JsonResponse
    {
        $roomDto = $this->roomService->createRoom($request->validated());

        return response()->json([
            'message' => 'Sala creada exitosamente',
            'data' => $roomDto->toArray(),
        ], 201);
    }

    /**
     * Ver detalle de sala.
     */
    public function show(string $roomId): JsonResponse
    {
        $room = $this->roomService->getRoom($roomId);
        $players = $this->roomService->listPlayers($roomId);

        return response()->json([
            'data' => [
                'room' => $room->toArray(),
                'players' => array_map(fn($p) => $p->toArray(), $players),
                'player_count' => count($players),
            ],
        ]);
    }

    /**
     * Unirse a una sala.
     */
    public function join(JoinRoomRequest $request, string $roomId): JsonResponse
    {
        $user = $request->user();

        $result = $this->joinRoomAction->execute(
            roomId: $roomId,
            playerId: (string) $user->id,
            nickname: $request->input('nickname', $user->name ?? 'Jugador'),
            playerData: [
                'avatar' => $request->input('avatar'),
            ]
        );

        return response()->json([
            'message' => $result['message'],
            'data' => [
                'player' => $result['player']->toArray(),
                'room' => $result['room']->toArray(),
                'total_players' => count($result['players']),
            ],
        ]);
    }

    /**
     * Abandonar sala.
     */
    public function leave(Request $request, string $roomId): JsonResponse
    {
        $user = $request->user();

        $this->roomService->leaveRoom($roomId, (string) $user->id);

        return response()->json([
            'message' => 'Has abandonado la sala',
        ]);
    }

    /**
     * Actualizar configuración de sala.
     */
    public function update(CreateRoomRequest $request, string $roomId): JsonResponse
    {
        $room = $this->roomService->updateRoomConfig($roomId, $request->validated());

        return response()->json([
            'message' => 'Sala actualizada',
            'data' => $room->toArray(),
        ]);
    }

    /**
     * Eliminar/Cerrar sala.
     */
    public function destroy(Request $request, string $roomId): JsonResponse
    {
        $user = $request->user();

        // Validar que es el host
        $room = $this->roomService->getRoom($roomId);
        if ($room->hostId !== (string) $user->id) {
            return response()->json(['error' => 'Sin permisos'], 403);
        }

        $this->roomService->closeRoom($roomId, 'closed_by_host');

        return response()->json([
            'message' => 'Sala cerrada',
        ]);
    }
}

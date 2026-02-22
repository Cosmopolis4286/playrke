<?php

namespace App\Http\Controllers\Games\Bingo\Card;

use App\DTOs\Games\Bingo\Room\BingoRoomCreateDTO;
use App\DTOs\Games\Bingo\Room\BingoRoomUpdateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Room\BingoRoomRequest;
use App\Models\Games\Bingo\Room\BingoRoom;
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
final class BingoCardController extends Controller
{
    /**
     * @param BingoRoomService $service
     */
    public function __construct(
        private readonly \App\Services\Games\Bingo\Room\BingoRoomService $service,
        private readonly \App\Services\Security\UserContextService $userContext
    ) {
        $this->authorizeResource(BingoRoom::class, 'room');
    }

    /**
     * Resumen de salas de bingo (admin y commerce).
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->handleServiceInertia(
            function () {
                $userId = $this->userContext->resolveUserId();
                $response = $this->service->context('summary', $userId);

                return Inertia::render('Games/Bingo/Room/Summary', $response);
            },
            'No se pudo cargar la lista de salas de bingo.'
        );
    }

    /**
     * Crear una sala de bingo.
     *
     * @param BingoRoomRequest $request
     * @return JsonResponse
     */
    public function store(BingoRoomRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->create(
                $request->toDTO(BingoRoomCreateDTO::class)
            )
        );
    }

    /**
     * Obtener lista de salas de bingo filtradas por el contexto del usuario (admin o commerce).
     *
     * @return JsonResponse
     */
    public function fetchList(): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => ['data' => $this->service->fetchList()]
        );
    }

    /**
     * Mostrar detalle de una sala de bingo.
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function show(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => [
                'data' => $this->service->show($room),
            ]
        );
    }

    /**
     * Actualizar una sala de bingo.
     *
     * @param BingoRoomRequest $request
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function update(BingoRoomRequest $request, BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->update(
                $room,
                $request->toDTO(BingoRoomUpdateDTO::class)
            )
        );
    }

    /**
     * Eliminar una sala de bingo (soft delete).
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function delete(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->delete($room)
        );
    }

    /**
     * Eliminar una sala de bingo permanentemente (hard delete).
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function destroy(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->destroy($room)
        );
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una sala de bingo.
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function toggleStatus(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->toggleStatus($room)
        );
    }

    /**
     * Copiar una sala de bingo como nuevo borrador (status DRAFT).
     *
     * @param BingoRoom $room
     * @return JsonResponse
     */
    public function copy(BingoRoom $room): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->service->copy($room)
        );
    }
}
